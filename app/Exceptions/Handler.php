<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // protected function unauthenticated($request, AuthenticationException $ex){
    //     return response()->json(['success' => false, 'message' => $ex->getMessage()], 401);
    // }

    public function render($request, Throwable $ex)
    {
        if ($ex instanceof AuthenticationException) {
            return response()->json(['success' => false,'err' => $ex->getMessage(), 'message' => 'Unauthorized'], 401);
        }
        return parent::render($request, $ex);
    }
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
