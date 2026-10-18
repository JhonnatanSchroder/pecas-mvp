<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegrationCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Integration-Token');

        if (! is_string($token) || $token === '') {
            return response()->json(['message' => 'Token de integração inválido.'], 401);
        }

        $company = Company::query()->where('integration_token_hash', hash('sha256', $token))->first();

        if ($company === null) {
            return response()->json(['message' => 'Token de integração inválido.'], 401);
        }

        $request->attributes->set('integration_company_id', $company->id);

        return $next($request);
    }
}
