<?php

namespace App\Http\Controllers\Admin\Network;

use App\Http\Controllers\Controller;
use App\Models\Devices;
use App\Models\DevicesFirmwares;
use App\Models\DevicesModels;
use App\Models\DevicesVendors;
use App\Repositories\Devices\DevicesRepository;
use App\Repositories\Snmp\SnmpRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Sites\UpdateAndStoreDeviceRequest;

class NetworkDevicesController extends Controller
{
    /** @var SnmpRepository */
    private $snmpRepository;

    /** @var DevicesRepository */
    private $deviceRepository;

    public function __construct()
    {
        $this->snmpRepository   = new SnmpRepository();
        $this->deviceRepository = new DevicesRepository();
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        $devices = Devices::with('firmware', 'model', 'vendor')->get();

        return view('admin.network.devices.index', compact('devices'));
    }

    /**
     * @return Factory|View
     */
    public function create()
    {
        return view('admin.network.devices.create');
    }

    public function edit(Request $request)
    {
        $device = Devices::with('model', 'vendor', 'firmware')->find($request->device);

        return view('admin.network.devices.edit', compact('device'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $device = Devices::find($request->device);
        $device->delete();

        flash('Устройство удалено')->success();

        return redirect()->route('network.devices.index');
    }

    public function show(Request $request)
    {
        $device = Devices::with('model', 'vendor', 'firmware')->find($request->device);

        return view('admin.network.devices.show', compact('device'));
    }

    public function update(UpdateAndStoreDeviceRequest $request)
    {
        $fill   = $request->validated();
        $device = Devices::find($request->device);
        $device->update($fill);
        flash('Данные успешно обнолены')->success();

        return redirect()->route('network.devices.show', $request->device);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // Getting vars from template
            $hostname      = $request->input('hostname');               // Hostname device
            $title         = $request->input('title');                  // Short description
            $snmpCommunity = $request->input('snmp_community');         // Device community
            $snmpPort      = $request->input('snmp_port');              // Device snmp port
            $snmpVersion   = $request->input('snmp_version');           // Device snmp version 1/2/3

            // Getting snmp flow from device
            $snmpFlow = $this->snmpRepository->getSnmpFlow(
                $hostname,
                $snmpCommunity
            );

            // Identification vendor
            $vendor      = $this->snmpRepository->getVendor($snmpFlow);
            $vendorClass = str_replace('-', '', $vendor); // Replace unused symbols

            // Checking vendor isset on system
            if ($vendor == null) {
                flash('Производитель не определен, либо устройства данного производителя не поддерживается')
                    ->warning();

                return redirect()->back()->withInput();
            }

            $firmwareClassFile = '\App\Repositories\Snmp\Vendors\\' . $vendorClass;
            $firmwareClass     = new $firmwareClassFile();

            // Getting vars from device
            $location        = $firmwareClass->getLocation($snmpFlow);
            $contact         = $firmwareClass->getContact($snmpFlow);
            $model           = $firmwareClass->getModel($snmpFlow);
            $platformType    = $firmwareClass->getPlatformType($model);
            $firmwareTitle   = $firmwareClass->getFirmware($snmpFlow);
            $firmwareVersion = $firmwareClass->getFirmwareVersion($snmpFlow);
            $uptimeDevice    = $firmwareClass->getUptime($snmpFlow);
            $packetsVersion  = $firmwareClass->getPacketsVersion($snmpFlow);
            $serialNumber    = $firmwareClass->getSerialNumber($snmpFlow);
            $humanModel      = $firmwareClass->getHumanModel($snmpFlow);
            $licenseLevel    = $firmwareClass->getLicenseLevel($snmpFlow);

            $firmware = $this->checkFirmware($firmwareTitle, $firmwareVersion);
            $vendorId = $this->checkVendor($vendor);
            $modelId  = $this->checkModel($model);
            $this->deviceRepository->saveDevice(
                $title,
                $hostname,
                $vendorId,
                $modelId,
                $firmware,
                $uptimeDevice,
                $contact,
                $location,
                $humanModel,
                $licenseLevel,
                $serialNumber,
                $packetsVersion,
                $platformType,
                $snmpPort,
                $snmpCommunity,
                $snmpVersion
            );
            flash('Хост добавлен')->success();

            return redirect()->route('network.devices.index');
        } catch (\Exception $e) {
            flash('Возникла ошибка при добавлении нового устройства! (' . $e->getMessage() . ')')->warning();

            return redirect()->back()->withInput();
        }
    }

    /**
     * @param string $vendorTitle
     * @return int
     */
    public function checkVendor(string $vendorTitle): int
    {
        $vendor = DevicesVendors::where('title', $vendorTitle)->first();
        if (empty($vendor)) {
            $vendor        = new DevicesVendors();
            $vendor->title = $vendorTitle;
            $vendor->save();

            return $vendor->id;
        } else {
            return $vendor->id;
        }
    }

    /**
     * @param string $modelTitle
     * @return int
     */
    public function checkModel(string $modelTitle): int
    {
        $model = DevicesModels::where('title', $modelTitle)->first();

        if (empty($model)) {
            $model        = new DevicesModels();
            $model->title = $modelTitle;
            $model->save();

            return $model->id;
        } else {
            return $model->id;
        }
    }

    /**
     * @param string $firmwareTitle
     * @param string $firmwareVersion
     * @return int
     */
    public function checkFirmware(string $firmwareTitle, string $firmwareVersion = null): int
    {
        $firmware = DevicesFirmwares::where('title', $firmwareTitle)
            ->where('version', $firmwareVersion)
            ->first();

        if (empty($firmware)) {
            $firmware          = new DevicesFirmwares();
            $firmware->title   = $firmwareTitle;
            $firmware->version = $firmwareVersion;
            $firmware->save();

            return $firmware->id;
        } else {
            return $firmware->id;
        }
    }
}
