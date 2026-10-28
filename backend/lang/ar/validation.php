<?php

return [

    /*
    |--------------------------------------------------------------------------
    | رسائل التحقق من المدخلات
    |--------------------------------------------------------------------------
    */

    'accepted' => 'يجب قبول :attribute.',
    'after' => ':attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => ':attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'array' => ':attribute يجب أن يكون قائمة.',
    'before' => ':attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => ':attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => ':attribute يجب أن يحتوي بين :min و :max عناصر.',
        'file' => 'حجم :attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون بين :min و :max.',
        'string' => ':attribute يجب أن يكون بين :min و :max محرفاً.',
    ],
    'boolean' => ':attribute يجب أن يكون صح أو خطأ.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => ':attribute ليس تاريخاً صالحاً.',
    'different' => ':attribute يجب أن يختلف عن :other.',
    'digits' => ':attribute يجب أن يتكون من :digits أرقام.',
    'email' => ':attribute يجب أن يكون بريداً إلكترونياً صالحاً.',
    'enum' => ':attribute المختار غير صالح.',
    'exists' => ':attribute المختار غير موجود.',
    'file' => ':attribute يجب أن يكون ملفاً.',
    'image' => ':attribute يجب أن يكون صورة.',
    'in' => ':attribute المختار غير صالح.',
    'integer' => ':attribute يجب أن يكون عدداً صحيحاً.',
    'max' => [
        'array' => ':attribute يجب ألا يحتوي أكثر من :max عناصر.',
        'file' => 'حجم :attribute يجب ألا يتجاوز :max كيلوبايت.',
        'numeric' => ':attribute يجب ألا يتجاوز :max.',
        'string' => ':attribute يجب ألا يتجاوز :max محرفاً.',
    ],
    'mimes' => ':attribute يجب أن يكون ملفاً من نوع: :values.',
    'min' => [
        'array' => ':attribute يجب أن يحتوي على الأقل :min عناصر.',
        'file' => 'حجم :attribute يجب أن يكون على الأقل :min كيلوبايت.',
        'numeric' => ':attribute يجب أن يكون على الأقل :min.',
        'string' => ':attribute يجب أن يكون على الأقل :min محارف.',
    ],
    'numeric' => ':attribute يجب أن يكون رقماً.',
    'password' => [
        'letters' => ':attribute يجب أن تحتوي على حرف واحد على الأقل.',
        'mixed' => ':attribute يجب أن تحتوي على حرف كبير وحرف صغير على الأقل.',
        'numbers' => ':attribute يجب أن تحتوي على رقم واحد على الأقل.',
        'symbols' => ':attribute يجب أن تحتوي على رمز واحد على الأقل.',
        'uncompromised' => ':attribute ظهرت في تسريب بيانات. الرجاء اختيار كلمة أخرى.',
    ],
    'prohibited_if' => ':attribute غير مسموح عندما يكون :other هو :value.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => ':attribute مطلوب.',
    'required_if' => ':attribute مطلوب عندما يكون :other هو :value.',
    'required_without' => ':attribute مطلوب عند عدم وجود :values.',
    'string' => ':attribute يجب أن يكون نصاً.',
    'unique' => ':attribute مستخدم مسبقاً.',
    'url' => ':attribute يجب أن يكون رابطاً صالحاً.',

    /*
    |--------------------------------------------------------------------------
    | قيم تظهر داخل الرسائل
    |--------------------------------------------------------------------------
    */

    'values' => [
        'is_online' => [
            'true' => 'أونلاين',
            'false' => 'حضوري',
        ],
        'post_type' => [
            'event' => 'فعالية',
        ],
        'decision' => [
            'rejected' => 'مرفوض',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | أسماء الحقول
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'new_password' => 'كلمة المرور الجديدة',
        'otp_code' => 'رمز التحقق',
        'token' => 'رمز إعادة التعيين',
        'avatar' => 'الصورة الشخصية',
        'phone' => 'رقم الهاتف',
        'phone_number' => 'رقم الهاتف',
        'linkedin_url' => 'رابط LinkedIn',
        'short_bio' => 'النبذة',
        'national_id' => 'الرقم الوطني',
        'identity_front' => 'الوجه الأمامي للهوية',
        'identity_back' => 'الوجه الخلفي للهوية',

        'title' => 'العنوان',
        'description' => 'الوصف',
        'post_type' => 'نوع المنشور',
        'category_id' => 'التصنيف',
        'is_online' => 'نوع الحضور',
        'location' => 'الموقع',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'tags' => 'الوسوم',
        'media' => 'الوسائط',
        'action' => 'الإجراء',
        'content' => 'المحتوى',
        'rating' => 'التقييم',
        'share_text' => 'نص المشاركة',
        'reason' => 'سبب البلاغ',
        'reportable_type' => 'نوع البلاغ',
        'reportable_id' => 'العنصر المبلَّغ عنه',

        'website_url' => 'الموقع الإلكتروني',
        'address' => 'العنوان',
        'logo' => 'الشعار',
        'company_registration_number' => 'رقم السجل التجاري',
        'tax_id' => 'الرقم الضريبي',
        'documents' => 'الوثائق',
        'documents.commercial_register' => 'السجل التجاري',
        'documents.tax_document' => 'الوثيقة الضريبية',

        'amount' => 'المبلغ',
        'payment_method' => 'طريقة الدفع',
        'payment_intent_id' => 'رقم عملية الدفع',
        'posts_count' => 'عدد المنشورات',
        'price' => 'السعر',
        'featured_post_price' => 'سعر المنشور المميز',
        'featured_post_duration_days' => 'مدة التمييز بالأيام',

        'decision' => 'القرار',
        'rejection_reasons' => 'أسباب الرفض',
        'ban_reason' => 'سبب الحظر',
        'admin_notes' => 'ملاحظات الأدمن',
        'status' => 'الحالة',
        'note' => 'الملاحظة',
        'type' => 'النوع',
    ],
];
