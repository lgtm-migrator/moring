<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\Statistics\IdentificatorsController;
use App\Models\MoringVersions;
use App\Models\Settings;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MoringVersionChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'MoringVersionChecker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $httpClient = new Client();
        $url = Config::get('moring.bridgeUrl') . Config::get('moring.bridgeCreateIdentificatorUrl'); # Url getting from /config/moring.php
        $response = $httpClient->request('GET', $url, ['allow_redirects' => false]);

        $settings = new Settings();
        $settings->parameter = 'identificator';
        $settings->value = json_decode($response->getBody(), true);
        $settings->save();

        # Getting availible Moring versions from bridge
        $httpClient = new Client();
        $url = Config::get('moring.bridgeUrl') . Config::get('moring.bridgeCurrentMoringVersionUrl'); # Url getting from /config/moring.php
        $response = $httpClient->request('GET', $url,
            ['query' => ['identificator' => $identificator->getIdentificator()], 'allow_redirects' => false]);
        $versionsBridgeArray = json_decode($response->getBody(), true);

        foreach ($versionsBridgeArray as $human_version => $version) {
            try {
                $localVersionsArray = MoringVersions::pluck('version')->toArray();
                if (!in_array($version, $localVersionsArray)) {
                    $versions = new MoringVersions();
                    $versions->version = $version;
                    $versions->human_version = $human_version;
                    $versions->save();
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }
}
