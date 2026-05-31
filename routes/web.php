<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\SimulatorController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::post('transactions/save-simulation', [TransactionController::class, 'saveSimulation']);
Route::delete('transactions/delete-simulation-group', [TransactionController::class, 'deleteSimulationGroup']);
Route::resource('transactions', TransactionController::class)->except(['show', 'create', 'edit']);
Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
Route::resource('bills', BillController::class)->except(['show', 'create', 'edit']);
Route::post('bills/{bill}/pay', [BillController::class, 'pay'])->name('bills.pay');
Route::resource('simulator', SimulatorController::class)->except(['show', 'create', 'edit']);

use App\Http\Controllers\WorkflowController;
Route::get('workflows/{workflow}/state', [WorkflowController::class, 'state']);
Route::post('workflows/{workflow}/sync', [WorkflowController::class, 'sync']);
Route::post('workflows/{workflow}/simulate', [WorkflowController::class, 'simulate']);