<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css"
        integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/fontawesome.min.css" />
    <title>Prescription</title>
</head>

<body style="padding:20px;color:#323232;width:80%">
    <img src="https://ayadty.com/el3yada/logoBlank.png" style="width:120px;height:120px" srcset=""> 
    <h5 style="display:inline">{{$data['clinic_name']}}</h5> <br> <br>

    <h5 style="padding-left:10px;">Dr. {{$data['doctor_name']}}</h5>

    <hr style="padding-left:20px;max-width:90%">

    <div style="padding:20px">
        <div style="margin-bottom:1rem">
            <h5 style="display:inline;"> Name: {{$data['doctor_name']}}</h5>
            <h5 style="display:inline;position:relative;left:40%"> Name: {{$data['name']}}</h5>
        </div>
        <p style="max-width:90%">

            {{  $data['description']  }}


        </p>
    </div>

    
    <hr style="padding-left:20px;max-width:90%">
    <div style="padding:20px">
        
        <div>
            <i class="fa-duotone fa-phone"></i>
             {{$data['phone']}}
        </div>
        <div >
           <i class="fa-regular fa-location-dot"></i>
            
       
      Location: {{$data['address']}}

        </div>
    </div>
   
</body>

</html>