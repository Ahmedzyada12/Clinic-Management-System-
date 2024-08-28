<?php

use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/testtt', function () {

// \Artisan::call('cache:clear');
// \Artisan::call('config:clear');
   
//         echo now();
//         echo '<br>';
//         echo date("Y-m-d H:i:s");

// });

use Illuminate\Http\Request;


Route::get('/test', function () {

    $data = [];
    $data['phone'] = +2011111111111;
    $data['clinic_name'] = 'clinic name';
    $data['doctor_name'] = 'Shun morphy';
    $data['date'] = 8-11-2022;
    $data['address'] = 'location';
    $data['description'] = 'this is test description.';
    $data['name'] = 'name1 name2';

    
    $pdf = Pdf::loadView('prescription', ['data'    => $data])->save('myfile.pdf');

    return 'ok2';

});




Route::get('/wts', function () {
    Http::withBody('{ "messaging_product": "whatsapp", "to": "+201110652177", "type": "template", "template": { "name": "hello_world", "language": { "code": "en_US" } } }', 'application/json')
        ->withToken('EAAKbuiBKVoABAOj5gcJZCzxYXnQwPlKBQzlTe8xvacrjZA1HHHNNZCc509hPrnXZAXnYzmxqD6sHqKt9VpcMIhuruG3nzgRRtnDh9AOcVDMXv2GGeN2KjKYX7zua70YNoIcGjQNbSGf36soZAIVPQ6tWOtSblZCmeVZCZAwKEtZCtH8NcpDEdZADeNpeQq5UFhQ4h8rdjVb5ZCzGEFUZBaybxOrz')
        ->post('https://graph.facebook.com/v15.0/100982099582514/messages');
        
        
        
          Http::withBody('{ "messaging_product": "whatsapp", "to": "201110652177", "type": "template", "template": { "name": "welcome", "language": { "code": "ar" },
      
          
              "components":[
                    {
                    "type": "body",
                    "parameters": [
                            {
                                "type": "text",
                                "text": "Omar"
                            }
                        ]
                    }
                ]

          
          
      } }','application/json')
    ->withToken('EAAKbuiBKVoABAA7xrhO19tQsb1Gf8bZCxoBj8ilp1q9tyqcUJELU4r7NiVQARgyzSx6oZA2tCZCHt0mwaZCGMCiE7Hq8D4hcAZAD7KE0FvvmyCliZBZAXy8WFaZAoyTKuTzM5r5JPdnJGygdPgj8waWjujjl3A9mzP96E05NfVk0Yieu2BZBObeDoTe03MfNSEtcyBvWZAAC179JNYdwZAzGY7q')
    ->post('https://graph.facebook.com/v15.0/100982099582514/messages');

});

Route::group(array('domain' => '{subdomain}.' . env('APP_NAME') . '.com'), function () {

    Route::get('/api/whatswebhook', function (Request $request) {
    
        if($request->hub_verify_token == 'VerifyToken123' && $request->hub_mode == 'subscribe')
        {
            return $request->hub_challenge;
        }
    
    });


});


// Route::get('/q', function () {    
//     //$pdf = Pdf::loadView('prescription')->save('myfile.pdf');
//     $visit = \App\Models\Visit::find(1);
//     // $visit->products()->attach([22,33,44,55,66]);
//     // return 'ok2';

//     $ids = '1,2';

//     $arr_ids = explode(',', $ids);
//     //return $arr_ids;

//     //checking the validity of ids.
//     foreach($arr_ids as $id)
//     {
//         $product = \App\Models\Inventory\Product::find($id);
//         if(!$product)   
//             return 'error';

//         if($product->qnty <= 0)
//             return 'error no enought quantity.';

//     }

//     $extra = $visit->extra_amount;
//     foreach($arr_ids as $id)
//     {
//         $product = null;
//         $product = \App\Models\Inventory\Product::find($id);

//         $extra += $product->price;
//         $product->qnty = $product->qnty - 1;
//         $product->save();
//     }


//     $visit->products()->attach($arr_ids);
//     $visit->extra_amount = $extra;
//     $visit->save();
//     $visit->products;
//     return $visit;


// });