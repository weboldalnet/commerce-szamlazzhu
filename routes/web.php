<?php

use Illuminate\Support\Facades\Route;
use Weboldalnet\CommerceSzamlazzhu\Http\Controllers\Admin\SzamlazzhuSettingController;
use Weboldalnet\CommerceSzamlazzhu\Http\Controllers\Admin\SzamlazzhuInvoiceController;

Route::domain(getAdminDomain())->middleware(['web', 'admin_share', 'auth:admin'])->prefix('webshop/szamlazzhu')->name('admin.webshop.szamlazzhu.')->group(function () {
    Route::get('/settings', [SzamlazzhuSettingController::class, 'index'])->name('settings');
    Route::post('/settings', [SzamlazzhuSettingController::class, 'update'])->name('settings.update');
    Route::post('/test-connection', [SzamlazzhuSettingController::class, 'testConnection'])->name('test-connection');
});

Route::domain(getAdminDomain())->middleware(['web', 'admin_share', 'auth:admin'])->prefix('webshop/invoices')->name('admin.webshop.invoices.')->group(function () {
    Route::get('/', [SzamlazzhuInvoiceController::class, 'index'])->name('index');
    Route::post('/{id}/void', [SzamlazzhuInvoiceController::class, 'void'])->name('void');
    Route::get('/{id}/download', [SzamlazzhuInvoiceController::class, 'download'])->name('download');
    Route::post('/{id}/resend', [SzamlazzhuInvoiceController::class, 'resend'])->name('resend');
    Route::post('/{id}/pay', [SzamlazzhuInvoiceController::class, 'pay'])->name('pay');
});
