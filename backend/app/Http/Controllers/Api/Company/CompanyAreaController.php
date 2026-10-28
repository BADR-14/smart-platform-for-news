<?php

namespace App\Http\Controllers\Api\Company;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * أساس كل Controllers لوحة الشركة (المسارات تحت /company والمحمية بـ role:Company)
 */
abstract class CompanyAreaController extends Controller
{
    protected function company(Request $request): Company
    {
        $company = $request->user()->company;

        if (! $company) {
            throw new ApiException('لا يوجد ملف شركة مرتبط بهذا الحساب.', 404, 'company_not_found');
        }

        return $company;
    }
}
