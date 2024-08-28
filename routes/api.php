<?php

use App\Models\User;
use App\Models\Visit;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Http\Traits\GeneralTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VisitedController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\MyFatoorahController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AuthUserController;
use App\Http\Controllers\VitalHistoryController;
use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\ExaminationTypeController;
use App\Http\Controllers\Api\SpecializationController;
use App\Http\Controllers\StatisticsController;




// Route::post('/register', [AuthUserController::class, 'register']);
Route::group([
    'middleware' => 'api',
], function ($router) {

    Route::post('logout', [AuthUserController::class, 'logout']);
    Route::get('me', [AuthUserController::class, 'me']);
});

Route::resource('assistants', AssistantController::class);
Route::match(['put', 'post'], 'assistants/{id}', [AssistantController::class, 'update']);
Route::get('/assistants/count', [AssistantController::class, 'countAssistants']);

Route::resource('patients', PatientController::class);
Route::match(['put', 'post'], 'patients/{id}', [PatientController::class, 'update']);
Route::get('/patients/count', [PatientController::class, 'countPatients']);
Route::get('/patients/by_doctor/{doctorId}', [PatientController::class, 'getPatientsByDoctor']);
Route::post('/register_patient', [PatientController::class, 'register_patient']);
Route::get('/patients', [PatientController::class, 'index']);
Route::post('/patient/save', [App\Http\Controllers\Api\PatientController::class, 'save']);
Route::get('/patients', [PatientController::class, 'searchPatientByName']);

Route::resource('doctors', DoctorController::class);
Route::get('/superAdminUsers', [DoctorController::class, 'getSuperAdminUsers']);
Route::get('/doctors/count', [DoctorController::class, 'countDoctors']);
Route::get('doctors/spec/{specialization_id}', [DoctorController::class, 'getDoctorBySpecialization']);
Route::get('/doctors', [DoctorController::class, 'searchDoctorByName']);


Route::match(['put', 'post'], 'doctors/{id}', [DoctorController::class, 'update']);
Route::resource('specializations', SpecializationController::class);
Route::match(['put', 'post'], 'specializations/{id}', [SpecializationController::class, 'update']);


Route::resource('payments', PaymentController::class);
Route::post('reservations/confirm/{reservation}', [ReservationController::class, 'confirm']);
Route::get('reservations/doctor/{doctor_id}', [ReservationController::class, 'getReservationsByDoctor']);


Route::get('/appointments/count-by-month', [AppointmentController::class, 'countAppointmentsByMonth']);

Route::post('/appointments', [AppointmentController::class, 'save']);
Route::put('appointments/{id}', [AppointmentController::class, 'update']);
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::delete('appointments/delete-all', [AppointmentController::class, 'deleteAll']);
Route::delete('appointments/{id}', [AppointmentController::class, 'delete']);
Route::get('appointments{doctor_id?}', [AppointmentController::class, 'getAppointments']);



//
Route::get('/examination-types', [ExaminationTypeController::class, 'index']);
Route::post('/examination-types', [ExaminationTypeController::class, 'store']);
Route::get('/examination-types/{id}', [ExaminationTypeController::class, 'show']);
Route::put('/examination-types/{id}', [ExaminationTypeController::class, 'update']);
Route::delete('/examination-types/{id}', [ExaminationTypeController::class, 'destroy']);
Route::get('/examination-types/doctor/{doctor_id}', [ExaminationTypeController::class, 'examinationTypeBydoctor']);

// Route::get('/vist', [App\Http\Controllers\Api\VisitController::class, 'list']);
// Route::get('/payments', [App\Http\Controllers\Api\VisitController::class, 'getVisitswithPayment'])->middleware('CheckAdmin:0,1,2');
// Route::post('/save', [App\Http\Controllers\Api\VisitController::class, 'save'])->middleware('CheckAdmin:0,1,2');

Route::resource('reservations', ReservationController::class);
Route::put('/reservations/cancel/{id}', [ReservationController::class, 'cancelReservation']);
Route::post('/reservations', [MyFatoorahController::class, 'initiatePayment'])->name('myfatoorah.initiate');
// Route::post('/reservations', [MyFatoorahController::class, 'index']);

Route::get('/myfatoorah/callback', [MyFatoorahController::class, 'callback'])->name('myfatoorah.callback');

Route::put('/reservations/{id}/visit', [VisitedController::class, 'updateStatusToVisited']);

Route::post('/register', [PatientController::class, 'register']);
Route::post('/login', [AuthUserController::class, 'login']);

Route::resource('vital-histories', VitalHistoryController::class);
Route::get('/vital-history/patient/{id}', [VitalHistoryController::class, 'VitalhistoryBYpatient']);


Route::resource('settings', SettingController::class);
Route::get('/settings/doctor/{doctor_id}', [SettingController::class, 'getSettingsByDoctor']);

//Statistics
Route::get('statistics', [StatisticsController::class, 'count']);
Route::get('payment/totals', [StatisticsController::class, 'getMonthlyTotalsByYear']);

// Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::resource('roles', RoleController::class);
Route::match(['put', 'post'], 'roles/{id}', [RoleController::class, 'update']);
Route::resource('permissions', PermissionController::class);


// Route::middleware('auth:sanctum')->group(function () {
//     // Get Authenticated User
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });


// });

// Define 'update' route separately
//assistants

Route::post('/registerNewClient', [App\Http\Controllers\Api\GneneralController::class, 'registerNewClient']);

Route::post('/contactUs', [App\Http\Controllers\Api\GneneralController::class, 'contactus']);


//my routs
Route::get('/whatsapp/webhook', function (Request $request) {

    return $request->all();
});


// SAAS Application Routes (For clients)
// Route::group(array('domain' => '{subdomain}.' . env('APP_NAME') . '.com'), function () {




//     Route::group(['middleware'  => ['CheckLanguage', 'SubdomainDatabase']], function () {

//         Route::post('sendMail', [App\Http\Controllers\Api\UserController::class, 'sendMail']);

//         Route::get('getDoctors', [App\Http\Controllers\Api\DoctorController::class, 'getDoctors']);  //get ten  doctors by role
//         Route::get('/getSiteConfig', [App\Http\Controllers\Api\SiteConfigurationController::class, 'getSiteConfig']);
//         Route::post('/getServiceByType', [App\Http\Controllers\Api\SiteConfigurationController::class, 'getServiceByType']);       //get services based on type from url
//         Route::get('/getLimitService', [App\Http\Controllers\Api\SiteConfigurationController::class, 'getLimitService']);  //get latest four services
//         Route::get('/getClinicInfo', [App\Http\Controllers\Api\UserController::class, 'getClinicInfo']);

//         // fawzi



//         // Route::get('/testq', function(){
//         //     $user = new User();
//         //     $amount = $user->getAmount(8, 'diagnosis');
//         //     return response()->json($amount);
//         // });


//         ############################################
//         #          Authentication routes           #
//         ############################################

//         /*Reset password routes with link.*/
//         Route::post('/subdomainChkr', [App\Http\Controllers\Api\AuthController::class, 'checkSubdomain']);
//         Route::post('/RequestPasswordReset', [App\Http\Controllers\Api\AuthController::class, 'RequestPasswordReset']);
//         Route::post('/PasswordReset', [App\Http\Controllers\Api\AuthController::class, 'PasswordReset']);
//         /*end reset password routes with link*/

//         /*reset password routes with code.*/
//         Route::post('/requestCodeResetPassword', [App\Http\Controllers\Api\AuthController::class, 'requestCodeResetPassword'])->name('requestCodeResetPassword');
//         Route::post('/CheckTheCodePasswordReset', [App\Http\Controllers\Api\AuthController::class, 'CheckTheCodePasswordReset'])->name('CheckTheCodePasswordReset');
//         Route::post('/passwordResetWithCode', [App\Http\Controllers\Api\AuthController::class, 'passwordResetWithCode'])->name('passwordReset');
//         /*end reset password routes with code.*/


//         Route::group(['middleware' => 'CheckAdmin:0,1,2'], function () {
//             Route::post('/getUserData', [App\Http\Controllers\Api\AuthController::class, 'getUserData']);
//         });

//         ############################################
//         #          configs Management         #
//         ############################################
//         Route::group(['prefix' => 'configs', 'middleware' => 'CheckAdmin:0'], function () {
//             Route::post('/storeSiteConfig', [App\Http\Controllers\Api\SiteConfigurationController::class, 'storeSiteConfig']);
//             Route::post('/storeServicesBlogs', [App\Http\Controllers\Api\SiteConfigurationController::class, 'storeServicesBlogs']);
//             Route::post('/updateServiceBlog/{id}', [App\Http\Controllers\Api\SiteConfigurationController::class, 'updateServiceBlog']);
//             Route::get('/deleteServiceBlog/{id}', [App\Http\Controllers\Api\SiteConfigurationController::class, 'deleteServiceBlog']);
//             Route::post('/resetImages', [App\Http\Controllers\Api\SiteConfigurationController::class, 'ResetImages']);
//         });

//         ############################################
//         #          Inventory Management            #
//         ############################################
//         Route::group(['prefix' => 'inventory', 'middleware' => 'CheckAdmin:0,1'], function () {

//             ############################################
//             #          Category Management             #
//             ############################################
//             Route::group(['prefix' => 'category'], function () {
//                 Route::get('/', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'list'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/save', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/{id}/update', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('/{id}/delete', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//                 //Route::post('/MultiDelete', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'deleteAll']);
//                 Route::get('/search', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'search'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/multiDelete', [App\Http\Controllers\Api\Inventory\CategoryController::class, 'multiDelete'])->middleware('CheckAdmin:0,1,2');
//             });


//             Route::get('/service', [App\Http\Controllers\Api\Inventory\ProductController::class, 'listServices'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/service/save', [App\Http\Controllers\Api\Inventory\ProductController::class, 'saveService'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/service/{id}/update', [App\Http\Controllers\Api\Inventory\ProductController::class, 'updateService'])->middleware('CheckAdmin:0,1,2');



//             ############################################
//             #          Product Management              #
//             ############################################
//             Route::group(['prefix' => 'product'], function () {
//                 Route::get('/', [App\Http\Controllers\Api\Inventory\ProductController::class, 'list'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/save', [App\Http\Controllers\Api\Inventory\ProductController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('/{id}/delete', [App\Http\Controllers\Api\Inventory\ProductController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/{id}/update', [App\Http\Controllers\Api\Inventory\ProductController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('/search', [App\Http\Controllers\Api\Inventory\ProductController::class, 'search'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('byCategory', [App\Http\Controllers\Api\Inventory\ProductController::class, 'listByCategory'])->middleware('CheckAdmin:0,1,2');

//                 Route::get('/item-card/{id}', [App\Http\Controllers\Api\Inventory\ProductController::class, 'itemCard'])->middleware('CheckAdmin:0,1,2');

//                 Route::get('/detailsOfProductProcess/{id}', [App\Http\Controllers\Api\Inventory\ProductController::class, 'detailsOfProductProcess'])->middleware('CheckAdmin:0,1,2');                 //get detailsOfProductProcess =>income and out


//                 ############################################
//                 #      Product Quantity Management         #
//                 ############################################

//                 Route::group(['prefix' => 'qnty'], function () {
//                     Route::post('/save', [App\Http\Controllers\Api\Inventory\ProductQntyController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//                     Route::post('/{id}/update', [App\Http\Controllers\Api\Inventory\ProductQntyController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//                     Route::get('/{id}/delete', [App\Http\Controllers\Api\Inventory\ProductQntyController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//                 });
//             });
//         });



//         ################################################
//         #           Doctors Management                 #
//         ################################################
//         Route::group(['prefix' => 'doctors', 'middleware' => 'CheckAdmin:0,1,2'], function () {

//             Route::group(['prefix' => 'payment', 'middleware' => 'CheckAdmin:0'], function () {
//                 Route::post('/update', [App\Http\Controllers\Api\DoctorPaymentController::class, 'update']);
//                 Route::get('/getData', [App\Http\Controllers\Api\DoctorPaymentController::class, 'getData']);
//             });

//             Route::get('/', [App\Http\Controllers\Api\DoctorController::class, 'list']);
//             Route::get('/getAll', [App\Http\Controllers\Api\DoctorController::class, 'getAll']);
//             Route::post('/save', [App\Http\Controllers\Api\DoctorController::class, 'save']);
//             Route::post('/{id}/update', [App\Http\Controllers\Api\DoctorController::class, 'update']);
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\DoctorController::class, 'delete']);
//             Route::get('/search', [App\Http\Controllers\Api\DoctorController::class, 'search']);
//         });

//         ################################################
//         #           Assistants Management              #
//         ################################################
//         // Route::group(['prefix' => 'assistant', 'middleware' => 'CheckAdmin:0'], function () {
//         Route::group(['prefix' => 'assistant'], function () {
//             Route::get('/', [App\Http\Controllers\Api\AssistantController::class, 'list']);
//             Route::post('/save', [App\Http\Controllers\Api\AssistantController::class, 'save']);
//             Route::post('/{id}/update', [App\Http\Controllers\Api\AssistantController::class, 'update']);
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\AssistantController::class, 'delete']);
//             Route::post('/MultiDelete', [App\Http\Controllers\Api\AssistantController::class, 'deleteAll']);
//             Route::get('/search', [App\Http\Controllers\Api\AssistantController::class, 'search']);
//         });


//         ################################################
//         #           Appointments Management            #
//         ################################################
//         Route::group(['prefix' => 'appointment'], function () {
//             Route::get('/', [App\Http\Controllers\Api\AppointmentController::class, 'list'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/save', [App\Http\Controllers\Api\AppointmentController::class, 'save'])->middleware('CheckAdmin:0,1');
//             Route::post('/{id}/update', [App\Http\Controllers\Api\AppointmentController::class, 'update'])->middleware('CheckAdmin:0,1');
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\AppointmentController::class, 'delete'])->middleware('CheckAdmin:0,1');
//             Route::post('/filter', [App\Http\Controllers\Api\AppointmentController::class, 'filterAppointments'])->middleware('CheckAdmin:0,1,2');
//         });

//         ################################################
//         #           Patient Management                 #
//         ################################################
//         Route::group(['prefix' => 'patient'], function () {
//             Route::get('/', [App\Http\Controllers\Api\PatientController::class, 'list'])->middleware('CheckAdmin:0,1');
//             // Route::post('/save', [App\Http\Controllers\Api\PatientController::class, 'save']);
//             Route::post('/{id}/update', [App\Http\Controllers\Api\PatientController::class, 'update'])->middleware('CheckAdmin:0,1');
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\PatientController::class, 'delete'])->middleware('CheckAdmin:0,1');
//             Route::get('/{generated_id}/found', [App\Http\Controllers\Api\PatientController::class, 'searchByGeneratedId'])->middleware('CheckAdmin:0,1');
//             Route::get('/{id}/view', [App\Http\Controllers\Api\PatientController::class, 'getById'])->middleware('CheckAdmin:0,1');
//             Route::get('/GetByName', [App\Http\Controllers\Api\PatientController::class, 'GetByName'])->middleware('CheckAdmin:0,1');


//             #############################################################################################
//             #                                                                                           #
//             #                                   New Updates                                             #
//             #                                                                                           #
//             #############################################################################################
//             Route::get('/{patient_id}/appointments', [App\Http\Controllers\Api\PatientController::class, 'getAppointments'])->middleware('CheckAdmin:0,1,2');
//             Route::get('{patient_id}/vitalSigns', [App\Http\Controllers\Api\Patient\VitalSignController::class, 'getByPatient'])->middleware('CheckAdmin:0,1,2');
//             Route::get('{patient_id}/caseHistories', [App\Http\Controllers\Api\Patient\CaseHistoryController::class, 'getByPatient'])->middleware('CheckAdmin:0,1,2');

//             Route::get('{patient_id}/prescriptions', [App\Http\Controllers\Api\VisitController::class, 'getPrescriptionsByPatient'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/updatePrescription',  [App\Http\Controllers\Api\VisitController::class, 'editDescription'])->middleware('CheckAdmin:0,1,2');

//             Route::get('{patient_id}/documents', [App\Http\Controllers\Api\VisitController::class, 'getDocumentsByPatient'])->middleware('CheckAdmin:0,1,2');
//             Route::post('removeDocument', [App\Http\Controllers\Api\VisitController::class, 'removeFileFromVisit'])->middleware('CheckAdmin:0,1,2');
//             Route::post('addDocument', [App\Http\Controllers\Api\VisitController::class, 'addFileToVisit'])->middleware('CheckAdmin:0,1,2');
//             Route::post('addSingleDocument', [App\Http\Controllers\Api\VisitController::class, 'addSingleFileToVisit'])->middleware('CheckAdmin:0,1,2');

//             Route::group(['prefix' => 'vitalSigns'], function () {
//                 Route::post('/save', [App\Http\Controllers\Api\Patient\VitalSignController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/{id}/update', [App\Http\Controllers\Api\Patient\VitalSignController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('/{id}/delete', [App\Http\Controllers\Api\Patient\VitalSignController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//             });

//             Route::group(['prefix' => 'caseHistory'], function () {
//                 Route::post('/save', [App\Http\Controllers\Api\Patient\CaseHistoryController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//                 Route::post('/{id}/update', [App\Http\Controllers\Api\Patient\CaseHistoryController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//                 Route::get('/{id}/delete', [App\Http\Controllers\Api\Patient\CaseHistoryController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//             });
//         });

//         ################################################
//         #           Visits Management                  #
//         ################################################
//         Route::group(['prefix' => 'visit'], function () {
//             Route::get('/', [App\Http\Controllers\Api\VisitController::class, 'list'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/payments', [App\Http\Controllers\Api\VisitController::class, 'getVisitswithPayment'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/save', [App\Http\Controllers\Api\VisitController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\VisitController::class, 'delete'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/{generated_id}/found', [App\Http\Controllers\Api\VisitController::class, 'searchByGeneratedId'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/{id}/view', [App\Http\Controllers\Api\VisitController::class, 'getById'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/filterVisits', [App\Http\Controllers\Api\VisitController::class, 'VisistsInRange'])->middleware('CheckAdmin:0,1,2');

//             Route::post('/getTotalAmountForPatients', [App\Http\Controllers\Api\VisitController::class, 'getTotalAmountForPatients'])->middleware('CheckAdmin:0,1,2'); //get total amount for patients based on date

//             Route::get('/{patient_id}/getVisitsWithPrdoucts', [App\Http\Controllers\Api\VisitController::class, 'getVisitsWithPrdoucts'])->middleware('CheckAdmin:0,1,2');
//             // Route::get('/sendSMS', [App\Http\Controllers\Api\VisitController::class, 'sendWhatsAppMessage']);
//             Route::post('/{id}/update', [App\Http\Controllers\Api\VisitController::class, 'update']);
//             Route::post('/updateVisit/{id}', [App\Http\Controllers\Api\VisitController::class, 'updateVisit']);
//         });


//         ################################################
//         #           Billing Management                  #
//         ################################################
//         Route::group(['prefix' => 'billing'], function () {
//             Route::get('/', [App\Http\Controllers\Api\BillingController::class, 'billingInfo'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/pay', [App\Http\Controllers\Api\BillingController::class, 'pay'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/payPatient', [App\Http\Controllers\Api\BillingController::class, 'payPatient'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/addPromocode', [App\Http\Controllers\Api\BillingController::class, 'addPromocode'])->middleware('CheckAdmin:0,1,2');
//         });


//         ################################################
//         #           General User Management            #
//         ################################################
//         Route::group(['prefix' => 'user', 'middleware' => 'CheckAdmin:0,1,2'], function () {
//             Route::post('/updateUser', [App\Http\Controllers\Api\UserController::class, 'update']);


//             Route::post('/editCredentials', [App\Http\Controllers\Api\UserController::class, 'editCredentials']);
//             Route::get('/changeStatus', [App\Http\Controllers\Api\UserController::class, 'changeStatus']);

//             /* Clinic Data */
//             Route::post('/editClinicInfo', [App\Http\Controllers\Api\UserController::class, 'editClinicInfo']);
//             Route::get('/returnClinicInfo', [App\Http\Controllers\Api\UserController::class, 'returnClinicInfo']);

//             Route::get('/dashboard', [App\Http\Controllers\Api\UserController::class, 'dashbaord']);
//             Route::get('/myProfile', [App\Http\Controllers\Api\UserController::class, 'myProfile']);
//             Route::get('/getPermissions/{id}', [App\Http\Controllers\Api\UserController::class, 'getPermissions']);
//             Route::post('/updatePermissions/{id}', [App\Http\Controllers\Api\UserController::class, 'updatePermissions']);
//         });

//         Route::get('/patient/billing/acceptance/post_pay', [App\Http\Controllers\Api\BillingController::class, 'callbackPatient']);


//         // DB::connection('mysql_kyan')->table('test')->insert([
//         //  'test' => json_encode($request->all())
//         //  ]);
//         Route::post('/whatswebhook', [App\Http\Controllers\Api\WhatsAppBotController::class, 'whatsappWebhook']);


//         ################################################
//         #           patient type Management            #
//         ################################################

//         Route::group(['prefix' => 'patientType'], function () {
//             Route::get('/', [App\Http\Controllers\Api\PatientTypeController::class, 'list'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/getAll', [App\Http\Controllers\Api\PatientTypeController::class, 'getAll'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/save', [App\Http\Controllers\Api\PatientTypeController::class, 'save'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/{id}/update', [App\Http\Controllers\Api\PatientTypeController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/{id}/delete', [App\Http\Controllers\Api\PatientTypeController::class, 'destroy'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/search', [App\Http\Controllers\Api\PatientTypeController::class, 'search'])->middleware('CheckAdmin:0,1,2');
//         });

//         ################################################
//         #           Expenses Controller                  #
//         ################################################
//         Route::group(['prefix' => 'expenses'], function () {
//             Route::post('/store', [App\Http\Controllers\Api\ExpenseController::class, 'store'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/delete/{id}', [App\Http\Controllers\Api\ExpenseController::class, 'delete'])->middleware('CheckAdmin:0,1,2');
//             Route::get('/show/{id}', [App\Http\Controllers\Api\ExpenseController::class, 'show'])->middleware('CheckAdmin:0,1,2');
//             Route::post('/update/{id}', [App\Http\Controllers\Api\ExpenseController::class, 'update'])->middleware('CheckAdmin:0,1,2');
//         });


//         Route::group(['prefix' => 'filter'], function () {
//             Route::post('/filterExpensesWithProducts', [App\Http\Controllers\Api\ExpenseController::class, 'filterExpensesWithProducts'])->middleware('CheckAdmin:0,1,2');   //get  expensis and  products by date
//             Route::post('/getTotalPaid', [App\Http\Controllers\Api\ExpenseController::class, 'getTotalPaid']);
//         });
//     });


//     Route::post('/save', [App\Http\Controllers\Api\AppointmentController::class, 'save']); //->middleware('CheckAdmin:0,1,2'); //->middleware('CheckAdmin:0,1');

// });




//callback payment function (doesn't need to be in subdomain)
Route::get('/billing/acceptance/post_pay', [App\Http\Controllers\Api\BillingController::class, 'callback']);