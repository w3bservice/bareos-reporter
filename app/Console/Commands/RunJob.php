<?php
/**
 * Bareos Reporter
 * Application for managing Bareos Backup Email Reports
 *
 * Run Job Artisan Command
 *
 * @license The MIT License (MIT) See: LICENSE file
 * @copyright Copyright (c) 2016 Matt Clinton
 * @author Matt Clinton <matt@laralabs.uk>
 * @website http://www.laralabs.uk/
 */

namespace App\Console\Commands;

use App\Catalogs;
use App\Contacts;
use App\Directors;
use App\Jobs;
use App\Schedules;
use App\Settings;
use App\Templates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RunJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reporter:run {job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs specified report job';

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
        $jobId = $this->argument('job');
        if(!empty($jobId) && $jobId != null)
        {
            $job = Jobs::find($jobId);
            if($job != null)
            {
                //GATHER DATA NEEDED FOR BOTH
                $director = Directors::find($job->director_id);
                $template = Templates::find($job->template_id);
                $catalog = Catalogs::getDirectorCatalog($job->director_id);
                $catalogName = $catalog->name;

                $clients = json_decode($job->clients);

                if($job->report_type == Jobs::REPORT_TYPE_ALL) {

                    $fileName = 'email'.$jobId.'.blade.php';
                    $code = $template->template_code;

                    $blade = fopen(resource_path('/views/email/'.$fileName), 'w');
                    if($blade !== false) {
                        fwrite($blade, $code);
                        $tempBlade = fclose($blade);

                        if ($tempBlade === true) {
                            /*
                             * Detect if we are using US or UK Dates
                             */
                            $ukDate = date('Y-m-d');
                            $usDate = date('Y-d-m');
                            $datePattern = '';
                            $ukCollection = DB::connection($catalogName)->table('Job')->where('StartTime', 'LIKE', '%' . $ukDate . '%')->get();
                            if (count($ukCollection) > 0) {
                                $datePattern = $ukDate;
                            } else {
                                $usCollection = DB::connection($catalogName)->table('Job')->where('StartTime', 'LIKE', '%' . $usDate . '%')->get();

                                if (count($usCollection) == 0) {
                                    Log::error('No US or UK Dates Found in Job Data');
                                    $deleteFile = unlink(resource_path('/views/email/' . $fileName));
                                    exit();
                                } else {
                                    $datePattern = $usDate;
                                }
                            }
                            /*
                             * Date Check End
                             */

                            // Let's build up the report data.
                            $clientData = array();
                            foreach ($clients as $client) {

                                //GATHER SUCCESS (T)
                                $successCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'T')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $successCount = count($successCollection);

                                //GATHER ERROR (f + E)
                                $errorCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'f')->where('JobStatus', '=', 'E')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $errorCount = count($errorCollection);

                                //GATHER WARNING (W)
                                $warningCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'W')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $warningCount = count($warningCollection);

                                $clientArray = array(
                                    'id' => $client->id,
                                    'name' => $client->name,
                                    'success' => $successCount,
                                    'error' => $errorCount,
                                    'warning' => $warningCount
                                );

                                array_push($clientData, $clientArray);
                            }

                            $data = array(
                                'clients' => $clientData,
                                'director' => $director,
                                'job' => $job
                            );
                            $email = Mail::send('email.email' . $jobId . '', $data, function ($message) use ($data) {
                                $message->from(Settings::getEmailFromAddress(), Settings::getEmailFromName());

                                $job = $data['job'];
                                $director = Directors::find($job->director_id);
                                $contacts = json_decode($job->contacts);
                                $message->subject('' . $director->director_name . ' Backup Report: ' . date('Y-m-d'));

                                foreach ($contacts as $contact) {
                                    $message->to($contact->email, $contact->name);
                                }
                            });
                            $deleteFile = unlink(resource_path('/views/email/' . $fileName));
                        }
                    }
                }elseif($job->report_type == Jobs::REPORT_TYPE_SEPARATE) {

                    $fileName = 'email'.$jobId.'.blade.php';
                    $code = $template->template_code;

                    $blade = fopen(resource_path('/views/email/'.$fileName), 'w');
                    if($blade !== false) {
                        fwrite($blade, $code);
                        $tempBlade = fclose($blade);

                        if ($tempBlade === true) {
                            /*
                             * Detect if we are using US or UK Dates
                             */
                            $ukDate = date('Y-m-d');
                            $usDate = date('Y-d-m');
                            $datePattern = '';
                            $ukCollection = DB::connection($catalogName)->table('Job')->where('StartTime', 'LIKE', '%' . $ukDate . '%')->get();
                            if (count($ukCollection) > 0) {
                                $datePattern = $ukDate;
                            } else {
                                $usCollection = DB::connection($catalogName)->table('Job')->where('StartTime', 'LIKE', '%' . $usDate . '%')->get();

                                if (count($usCollection) == 0) {
                                    Log::error('No US or UK Dates Found in Job Data');
                                    $deleteFile = unlink(resource_path('/views/email/' . $fileName));
                                    exit();
                                } else {
                                    $datePattern = $usDate;
                                }
                            }
                            /*
                             * Date Check End
                             */

                            // Let's build up the report data.
                            $clientData = array();
                            foreach ($clients as $client) {

                                //GATHER SUCCESS (T)
                                $successCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'T')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $successCount = count($successCollection);

                                //GATHER ERROR (f + E)
                                $errorCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'f')->where('JobStatus', '=', 'E')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $errorCount = count($errorCollection);

                                //GATHER WARNING (W)
                                $warningCollection = DB::connection($catalogName)->table('Job')->where('ClientId', '=', $client->id)->where('JobStatus', '=', 'W')->where('Type', '=', 'B')->where('StartTime', 'LIKE', '%' . $datePattern . '%')->get();
                                $warningCount = count($warningCollection);

                                $clientArray = array(
                                    'id' => $client->id,
                                    'name' => $client->name,
                                    'success' => $successCount,
                                    'error' => $errorCount,
                                    'warning' => $warningCount
                                );

                                array_push($clientData, $clientArray);

                                $data = [
                                    'clients'       => $clientData,
                                    'director'      => $director,
                                    'job'           => $job,
                                    'client_name'   => $client->name,
                                ];

                                $email = Mail::send('email.email' . $jobId . '', $data, function ($message) use ($data) {
                                    $message->from(Settings::getEmailFromAddress(), Settings::getEmailFromName());

                                    $job = $data['job'];
                                    $director = Directors::find($job->director_id);
                                    $contacts = json_decode($job->contacts);
                                    $message->subject('' . $director->director_name . ' Client Backup Report: '. date('d-m-Y') . ' '.$data['client_name']);

                                    foreach ($contacts as $contact) {
                                        $message->to($contact->email, $contact->name);
                                    }
                                });
                            }

                            $deleteFile = unlink(resource_path('/views/email/' . $fileName));
                        }
                    }
                }
            }
            else
            {
                Log::error('Job ID Invalid: '.$jobId);
            }
        }
    }
}
