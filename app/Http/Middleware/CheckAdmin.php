<?php

namespace App\Http\Middleware;

use App\Http\Traits\GeneralTrait;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use JWTAuth;
class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

     use GeneralTrait;
    public function handle(Request $request, Closure $next, ...$roles)
    {
        //return $next($request);
        auth()->shoulduse('api');
        $token = $request->header('token');
        $request->headers->set('token', (string)$token, true);
        $request->headers->set('Authorization', 'Bearer ' .$token, true);

        try{
            $user = JWTAuth::parseToken()->authenticate();

            //return response()->json($roles);
            if($user)
            {
              
                if(!in_array($user->role, $roles) || $user->status == 0){
                    
                    return $this->returnErrorResponse(__('general.unAuthenticated'));
                }
                /*prevent someone chenge the domain and get access to unauthorized data.*/
               // return response()->json($user);
                if($user->authenticated_subdomain == null || $user->authenticated_subdomain != $request->route('subdomain'))
                    return $this->returnErrorResponse(__('general.unAuthenticated'));
                    
                
            }else{
                return $this->returnErrorResponse(__('general.unAuthenticated'));
            }
        }catch(\Exception $exception)
        {
            if($exception instanceof TokenInvalidException)
                return $this->returnErrorResponse(__('general.token_invalid'));
            elseif($exception instanceof TokenExpiredException)
                return $this->returnErrorResponse(__('general.token_expired'));
            else
                return $this->returnErrorResponse(__('general.error_happend'));
        }

        return $next($request);

    }
}
