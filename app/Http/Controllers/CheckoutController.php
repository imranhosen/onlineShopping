<?php

namespace App\Http\Controllers;

use App\Customer;
use App\Shipping;
use Illuminate\Http\Request;
use Session;
use Mail;


class CheckoutController extends Controller
{
    public function index() {
        return view('front.checkout.checkout');
    }
    public function saveCustomerInfo(Request $request) {
        $this->validate($request, [
           'first_name' => 'required|regex:/^[\pL\s\-]+$/u',
           'last_name' => 'required|regex:/^[\pL\s\-]+$/u',
           'email' => 'required|email|unique:customers,email',
           'password' => 'required',
           'phone_number' => 'required|size:11|regex:/(01)[0-9]{9}/',
           'address' => 'required',
        ]);

        $customer = new Customer();
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->email = $request->email;
        $customer->password = bcrypt($request->password);
        $customer->phone_number = $request->phone_number;
        $customer->address = $request->address;
        $customer->token = str_random(10);
        $customer->save();




        Session::put('customerId', $customer->id);
        Session::put('customerName', $customer->first_name.' '.$customer->last_name);

        $data = $customer->toArray();


        Mail::send('front.mail.congratulation-mail', $data, function ($message) use ($data) {
            $message->to($data['email']);
            $message->subject('Congratulation Mail');
        });

        return redirect('/shipping-info');

}


    public function signUpConfirmation($token) {
        $customer = Customer::find($token);
        $customer->status = 1;
        $customer->token = ' ';
        $customer->save();
    }


    public function customerLogout() {
            Session::forget('customerId');
            Session::forget('customerName');

            return redirect('/');
    }


    public function customerLoginCheck(Request $request) {
        $customer = Customer::where('email', $request->email)->first();

        if($customer) {
            if(password_verify($request->password, $customer->password)) {
                Session::put('customerId', $customer->id);
                Session::put('customerName', $customer->first_name.' '.$customer->last_name);

                return redirect('/shipping-info');
            } else {
                return redirect('/checkout')->with('message', 'Invalid Password');
            }
        } else {
            return redirect('/checkout')->with('message', 'Invalid Email');
        }

    }





    public function showShippingInfo() {
        $customer = Customer::find(Session::get('customerId'));
        return view('front.checkout.shipping-info', ['customer'=>$customer]);
    }
    public function saveShippingInfo(Request $request) {
        $shipping = new Shipping();
        $shipping->full_name = $request->full_name;
        $shipping->email = $request->email;
        $shipping->phone_number = $request->phone_number;
        $shipping->address = $request->address;
        $shipping->save();

        Session::put('shippingId', $shipping->id);

        return redirect('/payment-info');
    }
    public function showPaymentForm() {
        return view('front.checkout.payment-form');
    }














}
