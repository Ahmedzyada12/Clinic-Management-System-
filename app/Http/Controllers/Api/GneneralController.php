<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\GeneralTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use mysqli;
use Illuminate\Support\Facades\Http;

class GneneralController extends Controller
{
    use GeneralTrait;

    private $reserved_words = [
        'support',
        'admin',
        'info',
        'hr',
        'sales',
        'mail',
        'host',
        'www',
        'ww2',
        'ww3',
    ];


    public function getBalance(){



    }
    public function registerNewClient(Request $request)
    {



        $validator = $this->validateRequest($request, [
            'subdomain'         => 'required|alpha_dash|unique:mysql_kyan.domains,subdomain',
            //'username'          => 'required|alpha_dash|unique:mysql_kyan.domains,username',
            //'password'          => 'required|alpha_dash',
            'adminFirstName'    => 'required|max:255',
            'adminLastName'     => 'required|max:255',
            'adminEmail'        => 'required|max:255',
            'adminPassword'     => 'required|max:255',
            'clinic_name_en'    => 'required|max:255',
            'doctor_number'     => 'required'
        ]);

        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);


        $subdomain = $this->validate_input($request->subdomain);
        if (in_array($subdomain, $this->reserved_words)) {
            $subdomain = '';
        }
        if ($subdomain == '')
            return $this->returnErrorResponse(__('general.invalidFormat', ['attr' => 'subdomain']));


        if ($request->promo_code) {
            $checked_promo = DB::connection('mysql_promocode')->table('users')->where('promocode', $request->promo_code)->get()->first();
            if (!$checked_promo)
                return $this->returnErrorResponse(__('general.promocodeNotFound'));
        }
        $servername = "localhost";
        $username = "root";
        $password = "004f76c85c8dc9e6";
        // $password = "";
        // Create connection
        $conn = new mysqli($servername, $username, $password);
        // Check connection
        if ($conn->connect_error) {
            return $this->returnErrorResponse("Connection failed: " . $conn->connect_error);
        }

        // new user name
        $dbUser = $username;
        $dbPass = $password;           // new user password

        // new database name
        $dbName = 'clnk_' . $subdomain;
        // DB::statement("CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

        $queries = array(
            "CREATE DATABASE `$dbName` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci",
            "CREATE USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPass'",
            "GRANT USAGE ON * . * TO '$dbUser'@'localhost' IDENTIFIED BY '$dbPass' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0",
            "GRANT SELECT , INSERT , UPDATE, DELETE ON `$dbName` . * TO '$dbUser'@'localhost'",
            "FLUSH PRIVILEGES"
        );

        foreach ($queries as $query) {
            $rs = $conn->query($query);
        }

        /** create the tables  */
        $conn = new mysqli($servername, $username, $password, $dbName);
        // Check connection
        if ($conn->connect_error) {
            return $this->returnErrorResponse("Connection failed: " . $conn->connect_error);
        }

        $dataObj = new \stdClass();
        $dataObj->first_name = $this->validate_input($request->adminFirstName);
        if ($dataObj->first_name == '')
            return $this->returnErrorResponse(__('general.invalidFormat', ['attr' => 'First name']));

        $dataObj->last_name = $this->validate_input($request->adminLastName);
        if ($dataObj->last_name == '')
            return $this->returnErrorResponse(__('general.invalidFormat', ['attr' => 'Last name']));

        $dataObj->email = $this->validate_input($request->adminEmail, true);
        if ($dataObj->email == '')
            return $this->returnErrorResponse(__('general.invalidFormat', ['attr' => 'Email']));

        $dataObj->password =  Hash::make($request->adminPassword);


        $dataObj->clinic_name_en = $this->validate_input($request->clinic_name_en);
        if ($dataObj->clinic_name_en == '')
            return $this->returnErrorResponse(__('general.invalidFormat', ['attr' => 'Clinic name in english']));



        // $dataa = json_encode($dataObj);

        // $commands = $this->insertHelper($dataa);
        // $conn->multi_query($commands);

        /*close connection*/
        $conn->close();

        /* Get 15 days free trail.*/
        $current_date = date('Y-m-d H:i:s');
        $expiry_date = date('Y-m-d H:i:s', strtotime($current_date . ' + 15 days'));

        DB::connection('mysql_kyan')->table('domains')->insert([
            'subdomain' => $subdomain,
            'db_name' => $dbName,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', '004f76c85c8dc9e6'),
            'expiry_date'   => $expiry_date,
            'promo_code'   => $request->promo_code,
            'doctor_name'   => $request->adminFirstName . ' ' . $request->adminLastName,
            'doctor_number'   => $request->doctor_number,
            'days'   => 15,
            'expired_promo_codes'   => '',
            'patient_payment'   => 0,
        ]);


        //add the seller commession should be moved to payment callback
        if ($request->promo_code) {
            // $seller = DB::connection('mysql_promocode')
            //             ->table('users')
            //             ->where('promocode', $request->promo_code)
            //             ->first();
            // $earned = ($seller->commession / 100) * $seller->offer;
            // $query = DB::connection('mysql_promocode')
            //             ->table('users')
            //             ->where('promocode', $request->promo_code);

            // $query->increment('balance', $earned);
            // $query->increment('history_balance', $earned);

        }
        Config::set('database.connections.mysql.database', $dbName);
        DB::purge('mysql');
        DB::reconnect('mysql');

        // Run migrations for the new database
        Artisan::call("migrate --path=/database/migrations --database=mysql --force");
        $user = DB::connection('mysql')->table('users')->insert([
            'first_name' => $request->adminFirstName,
            'last_name' => $request->adminLastName,
            'email' => $request->adminEmail,
            'password' => Hash::make($request->adminPassword),
            'phone' => $request->doctor_number,
            'role'=>0,
            'is_superAdmin'=>'superadmin',
        ]);

       //send whatsapp message
        $response = Http::withBody('
            {
        "messaging_product": "whatsapp",
        "to": "+2' . $request->doctor_number . '",
        "type": "template",
        "template": {
            "name": "welcome_for_doctor",
            "language": {
                "code": "en"
            },
            "components": [
                {
                    "type": "header",
                    "parameters": [
                        {
                            "type": "text",
                            "text": "' . $request->adminFirstName . ' ' . $request->adminLastName . '"
                        }
                    ]
                },
                {
                    "type": "body",
                    "parameters": [
                        {
                            "type": "text",
                            "text": "https://' . $subdomain . '.ayadty.com"
                        },
                        {
                            "type": "text",
                            "text": "' . $request->adminEmail . '"
                        },
                        {
                            "type": "text",
                            "text": "' . $request->adminPassword . '"
                        }
                    ]
                }
            ]
        }
    }
        ', 'application/json')
            ->withToken(env('WHATSAPP_API_KEY'))
            ->post('https://graph.facebook.com/v14.0/111701565072005/messages');

        $response = json_decode($response);
        // if(isset($response) && isset($response->error))
        //     return $this->returnErrorResponse('Invalid Number');


        //send welcome mail to the doctor.
        $details = [
            'doctor_name' => $request->adminFirstName . ' ' . $request->adminLastName,
            'subdomain' => 'https://' . $subdomain . '.ayadty.com'
        ];

        \Mail::to($request->adminEmail)->send(new \App\Mail\DoctorWelcomeMail($details));

        // 'doctor_name'   => $dataObj->first_name . ' ' . $dataObj->last_name,
        // 'doctor_number'   => $request->doctor_number,
        // 'status'   => $status,
        // 'paid_date'   => $paid_date,
        // 'days'   => $request->package,


        return $this->returnSuccessResponse(__('general.subscribed_success'));
    }

    private function validate_input($data, $email = false)
    {

        $data = strip_tags($data);
        $data = trim($data);
        $data = strtolower($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        if ($email)
            $data = preg_replace('/[^a-zA-Z0-9_@.]/', '', $data);
        else
            $data = preg_replace('/[^a-zA-Z0-9]/', '', $data);

        return $data;
    }


    public function contactus(Request $request)
    {

        $rules = [
            'email' => 'required',
            'name' => 'required',
            'body' => 'required',
        ];

        $validator = $this->validateRequest($request, $rules);
        if ($validator->fails())
            return $this->returnErrorResponse(__('general.validate_error'), $validator);
        $details = [];
        $details['email'] = $request->email;
        $details['name'] =  $request->name;
        $details['body'] = $request->body;

        \Mail::to('k.mohamed@kyanlabs.com')->send(new \App\Mail\ContactUsMail($details));

        return $this->returnSuccessResponse(__('general.contact_success'));
    }


    private function generatePassword()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $pass = str_shuffle($chars);
        $pass = substr($pass, 0, 10);
        return $pass;
    }
  private function insertHelper($api)
    {
        $data = json_decode($api);

        return <<<EOL
-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 17, 2022 at 09:23 PM
-- Server version: 5.7.37-log
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clnk_0x1`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0 available, 1 unavailable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `doctor_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_histories`
--

CREATE TABLE `case_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_infos`
--

CREATE TABLE `clinic_infos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_bio_ar` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_bio_en` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_address_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_address_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_department_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_department_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinic_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinic_fblink` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doctor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doctor_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` int(11) NOT NULL,
  `consultation_amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_payment_confs`
--

CREATE TABLE `doctor_payment_confs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `iframe_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `integration_online_card_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integration_mobile_wallet_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `hmac` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_payment_confs`
--

INSERT INTO `doctor_payment_confs` (`id`, `iframe_id`, `integration_online_card_id`, `integration_mobile_wallet_id`, `api_key`, `hmac`, `created_at`, `updated_at`) VALUES
(1, 'enter your data here...', 'enter your data here...', 'enter your data here...', 'enter your data here...', 'enter your data here...', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(79, '2014_10_12_000000_create_users_table', 1),
(80, '2014_10_12_100000_create_password_resets_table', 1),
(81, '2019_08_19_000000_create_failed_jobs_table', 1),
(82, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(83, '2022_09_06_121918_create_appointments_table', 1),
(84, '2022_09_07_071625_create_patient_infos_table', 1),
(85, '2022_09_07_093151_create_visits_table', 1),
(86, '2022_09_08_110555_create_clinic_infos_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_infos`
--

CREATE TABLE `patient_infos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `generated_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `qnty` int(11) NOT NULL DEFAULT '0',
  `products_info_qty` text DEFAULT NULL,
  `alert_qty` int(11) DEFAULT 10,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_qnties`
--

CREATE TABLE `product_qnties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `qnty` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` int(11) NOT NULL DEFAULT '1' COMMENT '0 S.Admin, 1 Assistant and 2 Patient',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '0 inavtive, 1 active',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authenticated_subdomain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `temp_codes` text COLLATE utf8mb4_unicode_ci,
  `amount` int(11) NOT NULL DEFAULT '450',
  `follow_up` int(11) NOT NULL DEFAULT '200'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `appointment_id` bigint(20) UNSIGNED NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0 Not 1 visited',
  `description` text COLLATE utf8mb4_unicode_ci,
  `file` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `extra_amount` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `main_amount_paid` int(11) NOT NULL DEFAULT '0' COMMENT '0 Not 1 paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visit_products`
--

CREATE TABLE `visit_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `heart_rate` int(11) NOT NULL COMMENT 'bpm',
  `systolic_blood_pressure` int(11) NOT NULL COMMENT 'mmHg',
  `diastolic_blood_pressure` int(11) NOT NULL COMMENT 'mmHg',
  `temperature` int(11) NOT NULL COMMENT '°C',
  `oxygen_saturation` int(11) NOT NULL COMMENT '%',
  `respiratory_rate` int(11) NOT NULL COMMENT 'bpm',
  `bmi_weight` int(11) NOT NULL COMMENT 'Kg',
  `bmi_height` int(11) NOT NULL COMMENT 'Cm',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `users` (`first_name`, `last_name`, `email`, `email_verified_at`, `password`, `phone`, `image`, `role`, `status`, `remember_token`, `created_at`, `updated_at`) VALUES
( '{$data->first_name}', '{$data->last_name}', '{$data->email}', NULL, '{$data->password}', '+201234567890', '', 0, 1, NULL, NULL, NULL);


CREATE TABLE `whatsapp_bot_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `profile_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra_date` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `clinic_infos` (`id`, `clinic_name_ar`, `clinic_name_en`, `clinic_bio_ar`, `clinic_bio_en`, `clinic_address_ar`, `clinic_address_en`, `clinic_department_ar`, `clinic_department_en`, `clinic_image`, `clinic_fblink`, `doctor_name`, `doctor_email`, `doctor_phone`, `amount`,`consultation_amount`, `created_at`, `updated_at`) VALUES
(1, '{$data->clinic_name_en}', '{$data->clinic_name_en}', 'Description', 'Description in English', 'Clinic Address in English', 'Clinic Address in Arabics', 'Clinic Department in Arabic', 'Clinic Department in english', NULL, 'https://facebook.com', '{$data->first_name}', '{$data->email}', '+201234567890',450,10, NULL, NULL);




--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_doctor_id_foreign` (`doctor_id`);

--
-- Indexes for table `case_histories`
--
ALTER TABLE `case_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_histories_patient_id_foreign` (`patient_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinic_infos`
--
ALTER TABLE `clinic_infos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctor_payment_confs`
--
ALTER TABLE `doctor_payment_confs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `patient_infos`
--
ALTER TABLE `patient_infos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_infos_user_id_foreign` (`user_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_category_id_foreign` (`category_id`);

--
-- Indexes for table `product_qnties`
--
ALTER TABLE `product_qnties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_qnties_product_id_foreign` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visits_user_id_foreign` (`user_id`),
  ADD KEY `visits_appointment_id_foreign` (`appointment_id`);

--
-- Indexes for table `visit_products`
--
ALTER TABLE `visit_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vital_signs_patient_id_foreign` (`patient_id`);

--
-- Indexes for table `whatsapp_bot_messages`
--
ALTER TABLE `whatsapp_bot_messages`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2713;

--
-- AUTO_INCREMENT for table `case_histories`
--
ALTER TABLE `case_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `clinic_infos`
--
ALTER TABLE `clinic_infos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_payment_confs`
--
ALTER TABLE `doctor_payment_confs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `patient_infos`
--
ALTER TABLE `patient_infos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_qnties`
--
ALTER TABLE `product_qnties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `visit_products`
--
ALTER TABLE `visit_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vital_signs`
--
ALTER TABLE `vital_signs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_bot_messages`
--
ALTER TABLE `whatsapp_bot_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `case_histories`
--
ALTER TABLE `case_histories`
  ADD CONSTRAINT `case_histories_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_infos`
--
ALTER TABLE `patient_infos`
  ADD CONSTRAINT `patient_infos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_qnties`
--
ALTER TABLE `product_qnties`
  ADD CONSTRAINT `product_qnties_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD CONSTRAINT `vital_signs_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;


CREATE TABLE `patient_pyaments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `patient_types`
--

CREATE TABLE `patient_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name_ar` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------



ALTER TABLE `patient_pyaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_pyaments_visit_id_foreign` (`visit_id`),
  ADD KEY `patient_pyaments_patient_id_foreign` (`patient_id`);

ALTER TABLE `patient_pyaments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `patient_pyaments`
  ADD CONSTRAINT `patient_pyaments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_pyaments_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;


--
-- Indexes for table `patient_types`
--
ALTER TABLE `patient_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `patient_types`
--
ALTER TABLE `patient_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;



COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

EOL;
    }
}
