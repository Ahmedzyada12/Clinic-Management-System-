<?php

namespace App\Http\Middleware;

use App\Http\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SubdomainDatabase
{

    use GeneralTrait;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $subdomain = $request->route('subdomain');

        //check the subdomain in kyanlabs database using mysql_kyan connection
        $data = DB::connection('mysql_kyan')->table('domains')->Where('subdomain', $subdomain)->first();
        if(!$data)
            return  $this->returnErrorResponse(__('general.subdomain_found_error'));


        //assign the new credentials to the client.
        Config::set('database.connections.mysql.database', $data->db_name);
        Config::set('database.connections.mysql.username', $data->username);
        Config::set('database.connections.mysql.password', $data->password);
        Config::set('database.default', 'mysql');
        

        return $next($request);
    }
}
