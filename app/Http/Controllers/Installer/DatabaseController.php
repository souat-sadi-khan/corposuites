<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function index()
    {
        return view('installer.step3');
    }

    public function store(Request $request)
    {
        $request->validate([
            'db_name'=>'required',
            'db_user'=>'required'
        ]);

        try {

            $config=[
                'driver'    =>  'mysql',
                'host'      =>  'localhost',
                'port'      =>  3306,
                'database'  =>  $request->db_name,
                'username'  =>  $request->db_user,
                'password'  =>  $request->db_pass,
                'charset'   =>  'utf8mb4',
                'collation' =>  'utf8mb4_unicode_ci'
            ];

            config(['database.connections.test_connection'=>$config]);

            DB::purge('test_connection');
            DB::connection('test_connection')->getPdo();
        } catch(\Exception $e){
            return response()->json([
                'status'  => false,
                'message' => 'Database connection failed. '. $e->getMessage()
            ]);
        }

        $this->updateEnv([
            'DB_CONNECTION' =>  'mysql',
            'DB_HOST'       =>  'localhost',
            'DB_PORT'       =>  3306,
            'DB_DATABASE'   =>  $request->db_name,
            'DB_USERNAME'   =>  $request->db_user,
            'DB_PASSWORD'   =>  $request->db_pass
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'Database connected and migrated successfully.'
        ]);
    }

    public function migration()
    {
        set_time_limit(300);
        ini_set('memory_limit','512M');

        // reconnect database
        DB::purge('mysql');
        DB::reconnect('mysql');

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);

            Artisan::call('db:seed');
        } catch(\Exception $e){
            return redirect()->to('/install/database')->with('error','Migration failed. '. $e->getMessage());
        }

        return redirect()->to('/install/admin')->with('success','Database connected and migrated successfully.');
    }

    private function updateEnv($data)
    {
        $env = file_get_contents(base_path('.env'));

        foreach($data as $key=>$value){

            $pattern = "/^#?\s*$key=.*/m";
            $replace = "$key=".$value;

            if(preg_match($pattern,$env)){
                $env = preg_replace($pattern,$replace,$env);
            } else {
                $env.="\n".$replace;
            }

        }

        file_put_contents(base_path('.env'),$env);

    }

}
