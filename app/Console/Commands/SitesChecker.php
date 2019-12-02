<?php

namespace App\Console\Commands;

use App\Models\Sites;
use App\Models\SitesHttpCodes;
use App\Models\SitesPhpVersions;
use App\Models\SitesWebServers;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SitesChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SitesChecker';

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
    public function handle(int $site_id = null)
    {
        if (is_null($site_id)) {
            $sites = Sites::get();
        } else {
            $sites[] = Sites::find($site_id);
        }

        foreach ($sites as $site) {

            $phpVersion = 0;
            $phpBranch = 0;
            $statusCode = 999;
            $webServerType = null;

            try {
                if ($site->checksList->use_file === 1) {
                    $httpClient = new Client();
                    $url = ($site->https === 1 && $site->checksList->check_https === 1) ? "https://" . $site->file_url : "http://" . $site->file_url;
                    $request = $httpClient->request('GET', $url, ['allow_redirects' => false]);
                    $response = $request->getBody();
                    $responseArray = json_decode($response, true);
                    $phpVersion = $responseArray['php-version'];
                    $statusCode = $request->getStatusCode();
                    $webServerType = $responseArray['web-server'];
                    $phpBranch = $responseArray['php-branch'];
                } else {
                    $httpClient = new Client();
                    $url = ($site->https === 1 && $site->checksList->check_https === 1) ? "https://" . $site->url : "http://" . $site->url;
                    $response = $httpClient->request('GET', $url, ['allow_redirects' => false]);
                    $phpVersion = $response->getHeader('X-Powered-By');
                    $webServerType = $response->getHeader('server')[0];

                    if (preg_match('/^[0-9]*/', $response->getStatusCode())) {
                        $statusCode = $response->getStatusCode();
                    }

                    if ($phpVersion != null) {
                        if (preg_match('/^PHP/', $phpVersion[0])) {
                            $phpVersion = preg_replace('/[^\d.]/', '', $phpVersion[0]);
                            $phpBranchRaw = explode('.', $phpVersion);
                            $phpBranchRaw = $phpBranchRaw[0] * 10000 + $phpBranchRaw[1] * 100 + $phpBranchRaw[2];
                            $phpBranch = Str::substr($phpBranchRaw, 0, 3);
                        }
                    }

                    $ssl = new SitesSSLChecker();
                    $ssl->handle($site->id);
                }
            } catch (\Exception $e) {

            }

            //   HTTP code saving process
            $http = SitesHttpCodes::where('site_id', $site->id)->first();
            if (isset($http)) {
                $http->http_code = $statusCode;
            } else {
                $fillable = ['site_id' => $site->id, 'http_code' => $statusCode];
                $http = new SitesHttpCodes($fillable);
            }
            $http->updated_at = Carbon::now();
            $http->save();


            //   WebServer type saving process
            $webServer = SitesWebServers::where('site_id', $site->id)->first();
            if (isset($webServer)) {
                $webServer->web_server = $webServerType;
            } else {
                $fillable = ['site_id' => $site->id, 'web_server' => $webServerType];
                $webServer = new SitesWebServers($fillable);
            }
            $webServer->updated_at = Carbon::now();
            $webServer->save();

            //    PHP version saving process
            $php = SitesPhpVersions::where('site_id', $site->id)->first();
            if (isset($php)) {
                $php->version = $phpVersion;
                $php->branch = $phpBranch;
            } else {
                $fillable = ['site_id' => $site->id, 'version' => $phpVersion, 'branch' => $phpBranch];
                $php = new SitesPhpVersions($fillable);
            }
            $php->updated_at = Carbon::now();
            $php->save();

        }
    }
}
