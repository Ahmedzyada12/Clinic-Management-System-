<?php

return [

    /*
    |--------------------------------------------------------------------------
    | general language line
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during any crud operation for various
    | messages that we need to display to the user.
    |
    */

    'add_success'                   => 'Data Added Successfully.',
    'add_error'                     => 'Error while inserting data into database.',

    'edit_success'                  => 'Data Updated Successfully.',
    'edit_error'                    => 'Error while updating data in database.',

    'delete_success'                => 'Data Deleted Successfully.',
    'delete_all'                    => ':num Records Deleted',

    'delete_error'                  => 'Error while deleting data from database.',
    'delete_error_found'            => "Can't delete the element, Delete the data that depend on that element.",

    'found_success'                 => 'All Data: ',
    'found_error'                   => 'Data not found in database.',

    //'validate_success'      =>'',
    'validate_error'                =>'404: Bad request, please review the validation errors:',
    'wrong_credentials'             =>'Email or password is incorrect.',
    
    'unAuthenticated'               =>'You are unauthenticated to visit this page.',


    'token_invalid'                 => 'Invalid Token.',
    'token_expired'                 => 'Expired Token.',
    'error_happend'                 => 'Opps! Error happened in payment operation please try again later.',

    'appointment_error'                 => 'This appointment has been reserved.',
    'CannotCancelVisit'                 => 'Reservation can\'t be canceled after 1 hour, please contact with clinic to remove the reservation.',

    'errorAppointment'                 => 'Please choose another appointment.',

    'problemHappen'                 => 'Something wrong happen plese try again later.',

    'subscribed_success'                 =>'The registeration done successfully. Now, You have 15-day free trial.',

    'invalidFormat'                 =>'please enter a valid format for :attr field.',


    'payment_not_secure'                 =>'Unsafe payment operation.',
    'paymentError'                       =>'Payment Error, Incomplete payment operation.',
    
    'paymentSuccess'                       =>'Congratulations, Your payment done successfully.',


    'promocodeNotFound'                       =>'Promo code not found on our database.',
    
    'promo_expired'                       =>'Promo code expired.',

    'add_promo_success'                       =>'Promo code Added Successfully.',


    'subdomain_found_error'                     => 'Subdomain doesn\'t exist, You can register it now.',


    'contact_success'                     => 'Thanks for contacting us',
    
    
    'patient_only_one_appointment'       => 'A patient can book only one appointment a day.',

    'qnty_error'       => 'There\'s no enough quantity in inventory.',
    
    'linkSent'          => 'The link sent to your email address.',


];
