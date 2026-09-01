<?php

return [
    'enabled' => env('COMMERCE_SZAMLAZZHU_ENABLED', false),

    /*
     * Számlázz.hu Agent hitelesítés
     */
    'authentication' => [
        'agent_api_key' => env('COMMERCE_SZAMLAZZHU_API_KEY', ''),
        'username' => env('COMMERCE_SZAMLAZZHU_USERNAME', ''),
        'password' => env('COMMERCE_SZAMLAZZHU_PASSWORD', ''),
    ],

    /*
     * Tárolási útvonalak (Laravel Storage-on belül)
     */
    'storage' => [
        'base_path' => 'private/commerce-szamlazzhu',
        'pdf_path' => 'pdf',
        'xml_path' => 'xmls',
        'log_path' => 'logs',
        'attachment_path' => 'attachments',
    ],

    /*
     * Alapértelmezett számla beállítások
     */
    'defaults' => [
        'invoice_type' => 'e-invoice', // e-invoice, paper
        'language' => 'hu',
        'currency' => 'HUF',
        'vat_key' => '27',
        'payment_deadline_days' => 8,
        'invoice_prefix' => '',
        'unit_of_measure' => 'db',
        'auto_pay_online' => true,
        'send_email_to_customer' => false,
    ],

    /*
     * Működési mód: automatic (online fizetés után azonnal), manual (csak admin gombra)
     */
    'billing_mode' => 'manual',

    /*
     * Eladó adatai (Számlázz.hu-ban is megadhatók, de itt felülírhatók)
     */
    'seller' => [
        'bank_name' => '',
        'bank_account_number' => '',
        'email_from_name' => '',
        'email_from_address' => '',
        'email_subject' => 'Számla értesítő - {invoice_number}',
        'email_content' => 'Tisztelt Vásárló! Mellékelten küldjük a(z) {invoice_number} számú számlát.',
        'use_signature' => false,
    ],
];
