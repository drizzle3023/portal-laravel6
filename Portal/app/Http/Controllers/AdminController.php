<?php


namespace App\Http\Controllers;

use App\Http\Models\Admin;
use App\Http\Models\Blacklist;
use App\Http\Models\Domain;
use App\Http\Models\Customer;
use App\Http\Models\Log;
use App\Http\Models\Product;
use App\Http\Models\SalesPerson;
use App\Http\Models\Whitelist;
use App\Http\Utils\Utils;
use Cassandra\Custom;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\In;

class AdminController
{
    public function index()
    {
        $user = session()->get('user');
        $user_type = session()->get('user-type');

        if (isset($user) && isset($user_type)) {
            return redirect('/dashboard');
        } else {
            return view('login');
        }
    }

    public function doLogin()
    {
        $email = request('login-username');
        $password = request('login-password');

        if (!isset($email)) {
            session()->flash('error-msg', 'Please enter valid email.');
            return redirect()->back();
        }
        if (!isset($password)) {
            session()->flash('error-msg', 'Please enter valid password.');
            return redirect()->back();
        }

        $admin = Admin::where('email', $email)->first();
        if (!isset($admin)) {

            $sales = SalesPerson::where('email', $email)->first();
            if (!isset($sales)) {

                $customer = Customer::where('email', $email)->first();
                if (!isset($customer)) {
                    session()->flash('error-msg', 'User not found.');
                    return redirect()->back();
                }
                if (!hash::check($password, $customer->password)) {
                    session()->flash('error-msg', 'Invalid password.');
                    return redirect()->back();
                }
                session()->put('user', $customer);
                session()->put('user-type', 3);
                return redirect('/dashboard');
            }

            if (!hash::check($password, $sales->password)) {
                session()->flash('error-msg', 'Invalid password.');
                return redirect()->back();
            }

            session()->put('user', $sales);
            session()->put('user-type', 2);
            return redirect('/dashboard');
        }

        if (!hash::check($password, $admin->password)) {
            session()->flash('error-msg', 'Invalid password.');
            return redirect()->back();
        }

        session()->put('user', $admin);
        session()->put('user-type', 1);
        return redirect('/dashboard');
    }

    public function logout()
    {
        session()->remove('user');
        session()->remove('user-type');
        return redirect('/login');
    }

    public function showForgotPasswordPage()
    {
        return view('forgot-password');
    }

    public function resetPassword() {

        $email = request('reminder-credential');
        $new_password = $this->randomPassword();
        $data = array('password'=>$new_password);

        try {
            Mail::send(['text' => 'mail'], $data, function ($message) use ($email) {
                $message->to($email, '')->subject
                ('Recovery Password');
                $message->from('portal@cubewerk.de', 'Web Portal');
            });
        } catch (Exception $e) {
            return view('forgot-password')->with([
                'message' => 'fail'
            ]);
        }

        $update_array = array(
        );
        $update_array['password'] = hash::make($new_password);

        Customer::where('email', $email)->update($update_array);

        return view('forgot-password')->with([
            'message' => 'success'
        ]);
    }

    function randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^&*()';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 20; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    public function setLocale($locale='en') {
        if (!in_array($locale, ['en', 'de'])){
            $locale = 'en';
        }
        session()->put('locale', $locale);
        return back();
    }

    public function dashboard()
    {
        if (session()->get('user-type') != 3) {
            $customers = Customer::count();
            $products = Product::count();
            $domains = Domain::count();
        } else {
            $current_user_id = session()->get('user')->id;
            $products = Product::where('customer_id', $current_user_id)->count();
            $domains = Domain::where('customer_id', $current_user_id)->count();
            $customers = 0;
        }

        return view('dashboard')->with([
            'products' => $products,
            'domains' => $domains,
            'customers' => $customers
        ]);
    }

    public function showProfilePage() {
        $user = session()->get('user');
        return view('profile')->with('user', $user);
    }

    public function editProfile() {
        $id = request('id');
        $password = request('password');

        $update_array = array(
        );

        if ($password != '') {
            $update_array['password'] = hash::make($password);
        }

        $user_type = session()->get('user-type');

        if (count($update_array) > 0) {
            if ($user_type === 1) {
                Admin::where('id', $id)->update($update_array);
                session()->put('user', Admin::where('id', $id)->first());
            } else {
                Customer::where('id', $id)->update($update_array);
                session()->put('user', Customer::where('id', $id)->first());
            }
        }

        return back()
            ->with('success', 'You have successfully updated your profile.');
    }

    public function showSalespersonPage() {
        $salesperson_list = SalesPerson::get();
        return view('salesperson_list')->with([
            'salesperson_array' => $salesperson_list
        ]);
    }

    public function showSalespersonAddPage() {
        return view('salesperson_add');
    }

    public function addSalesperson()
    {
        $email = request('email');
        $password = request('password');

        request()->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $employee = new SalesPerson();
        $employee->email = $email;
        $employee->password = hash::make($password);

        $employee->save();

        return back()
            ->with('success', "You have successfully added new salesperson.");
    }

    public function showSalespersonEditPage() {
        $id = request('id');
        if (isset($id)) {
            $salesperson = SalesPerson::where('id', $id)->first();
            if (isset($salesperson))
                return view('salesperson_edit')->with([
                    'salesperson' => $salesperson
                ]);
        }
        return back();
    }

    public function editSalesperson()
    {
        $id = request('id');
        $email = request('email');
        $password = request('password');

        request()->validate([
            'email' => 'required|email',
        ]);

        if (Admin::where('email', $email)->count() > 0 ||
            SalesPerson::where([
                ['email', $email],
                ['id', '<>', $id]
            ])->count() > 0 ||
            Customer::where('email', $email)->count() > 0
        ) {
            return back()
                ->with('fail', 'This email address is already used.');
        }

        if ($password != '') {
            SalesPerson::where('id', $id)->update([
                'email' => $email,
                'password' => hash::make($password),
            ]);
        } else {
            SalesPerson::where('id', $id)->update([
                'email' => $email,
            ]);
        }

        return back()
            ->with('success', 'You have successfully updated salesperson.');
    }

    public function delSalesperson()
    {
        $id = request('id');
        SalesPerson::where('id', $id)->delete();

        return Utils::makeResponse();
    }


    public function showCustomerListPage() {
        $customer_list = Customer::get();
        return view('customer_list')->with([
            'customer_array' => $customer_list
        ]);
    }

    public function showCustomerAddPage() {
        return view('customer_add');
    }

    public function addCustomer()
    {
        $email = request('email');
        //$password = request('password');

        request()->validate([
            'email' => 'required|email',
          //  'password' => 'required',
        ]);

        if (Admin::where('email', $email)->count() > 0 ||
            SalesPerson::where([
                ['email', $email]
            ])->count() > 0 ||
            Customer::where([
                ['email', $email]
            ])->count() > 0
        ) {
            return back()
                ->with('fail', 'This email address is already used.');
        }

        $new_password = $this->randomPassword();
        $data = array('password'=>$new_password);
        $sent_mail_flag = 0;

        try {
            Mail::send(['text' => 'mail'], $data, function ($message) use ($email) {
                $message->to($email, '')->subject
                ('Login Password');
                $message->from('portal@cubewerk.de', 'Web Portal');
            });
            $sent_mail_flag = 1;
        } catch (Exception $e) {
            $sent_mail_flag = 0;
        }

        $customer = new Customer();
        $customer->email = $email;
        $customer->password = hash::make($new_password);
        $customer->first_login_password = $new_password;

        $customer->save();

        if ($sent_mail_flag == 1) {
            return back()
                ->with([
                    'success' => "You have successfully added new customer.",
                    'password' => $new_password
                ]);
        } else {
            return back()
                ->with([
                    'success' => "You have successfully added new customer.",
                    'password' => $new_password,
                    'fail' => 'Sending email to customer is failed.'
                ]);
        }
    }

    public function showCustomerEditPage() {
        $id = request('id');
        if (isset($id)) {
            $customer = Customer::where('id', $id)->first();
            if (isset($customer))
                return view('customer_edit')->with([
                    'customer' => $customer
                ]);
        }
        return back();
    }

    public function editCustomer()
    {
        $id = request('id');
        $email = request('email');
        $password = request('password');

        request()->validate([
            'email' => 'required|email',
        ]);

        if (Admin::where('email', $email)->count() > 0 ||
            SalesPerson::where([
                ['email', $email]
            ])->count() > 0 ||
            Customer::where([
                ['email', $email],
                ['id', '<>', $id]
            ])->count() > 0
        ) {
            return back()
                ->with('fail', 'This email address is already used.');
        }

        if ($password != '') {
            Customer::where('id', $id)->update([
                'email' => $email,
                'password' => hash::make($password),
            ]);
        } else {
            Customer::where('id', $id)->update([
                'email' => $email,
            ]);
        }

        return back()
            ->with('success', 'You have successfully updated customer.');
    }

    public function deleteCustomer()
    {
        $id = request('id');
        if (isset($id)) {
            Customer::where('id', $id)->delete();
            Domain::where('customer_id', $id)->delete();
            Product::where('customer_id', $id)->delete();
        }

        return Utils::makeResponse();
    }

    public function showDomainPage()
    {
        $domains = [];
        $customers = [];
        $selected_customer_id = 0;

        if (session()->get('user-type') == 2) {

            $id = request('id');
            $customers = Customer::get();

            if (isset($id)) {
                $domains = Domain::where('customer_id', $id)->orderBy('customer_id')->with('customer')->get();
                $selected_customer_id = $id;
            } else {
                if (count($customers) > 0) {
                    $selected_customer_id = Customer::first()->id;
                    return redirect('/domains/'.$selected_customer_id);
                } else {
                    return redirect('/customer');
                }
            }

        } else if (session()->get('user-type') == 3){
            $current_user_id = session()->get('user')->id;
            $domains = Domain::where('customer_id', $current_user_id)->get();
        }

        return view('domain')->with([
            'domain_array' => $domains,
            'customer_array' => $customers,
            'selected_customer_id' => $selected_customer_id
        ]);
    }

    public function showDomainAddPage() {
        $customer_id = request('id');
        $customer_array = Customer::get();
        return view('domain_add')->with([
            'customer_array' => $customer_array,
            'selected_customer_id' => $customer_id
        ]);
    }

    public function addDomain() {
        $customer_id = request('customer-id');
        $domain = request('domain');
        request()->validate([
            'domain' => 'required',
        ]);
        if (isset($customer_id)) {
            $new_domain = new Domain();
            $new_domain->customer_id = $customer_id;
            $new_domain->domain = $domain;
            $new_domain->dns_active = 0;
            $new_domain->save();
            return back()
                ->with('success', 'You have successfully added new domain.');
        }
        return back()
            ->with('fail', 'Something went wrong.');
    }

    public function showDomainEditPage() {
        $id = request('id');
        if (isset($id)) {
            $domain = Domain::where('id', $id)->first();
            if (isset($domain)) {
                $customer_array = Customer::get();
                return view('domain_edit')->with([
                    'customer_array' => $customer_array,
                    'domain' => $domain
                ]);
            }
        }
        return back();
    }

    public function editDomain() {
        $id = request('id');
        $customer_id = request('customer-id');
        $domain = request('domain');

        request()->validate([
            'customer-id' => 'required',
            'domain' => 'required',
        ]);

        if (Domain::where([
            ['id', '<>', $id],
            ['domain', $domain]
        ])->count() > 0) {
            return back()
                ->with('fail', 'This domain is already used.');
        }

        Domain::where('id', $id)->update([
            'customer_id' => $customer_id,
            'domain' => $domain
        ]);

        return back()
            ->with('success', 'You have successfully updated domain.');
    }

    public function deleteDomain()
    {
        $id = request('id');
        if (isset($id)) {
            Product::where('domain_id', $id)->delete();
            Domain::where('id', $id)->delete();
        }

        return Utils::makeResponse();
    }

    public function checkDomain() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;

        if (Domain::where([
            ['id', $id],
            ['customer_id', $current_user_id],
        ])->count() > 0) {
            $domain_name = Domain::where('id', $id)->first()->domain;

            $hosts = array();
            getmxrr($domain_name, $hosts);

            $is_active = 0;
            foreach ($hosts as $host) {
                if (strpos($host, 'securepostfach') !== false) {
                    $is_active = 1;
                    break;
                }
            }
            Domain::where('id', $id)->update(['dns_active' => $is_active]);
        }

        $domains = Domain::where('customer_id', $current_user_id)->get();

        return view('domain')->with([
            'domain_array' => $domains
        ]);

    }

    public function showProductsPage()
    {
        $products = [];
        $customers = [];
        $selected_customer_id = 0;

        if (session()->get('user-type') == 2) {
            $id = request('id');
            $customers = Customer::get();

            if (isset($id)) {
                $products = Product::where('customer_id', $id)->orderBy('customer_id')->with('customer')->get();
                $selected_customer_id = $id;
            } else {
                if (count($customers) > 0) {
                    $selected_customer_id = Customer::first()->id;
                    return redirect('/products/'.$selected_customer_id);
                } else {
                    return redirect('/customer');
                }
            }

        } else if (session()->get('user-type') == 3) {
            $current_user_id = session()->get('user')->id;
            $products = Product::where('customer_id', $current_user_id)->orderBy('domain_id')->with('domain')->get();
        }

        for ($i = 0; $i < count($products); $i ++) {
            $used_count = Log::where('msg_to', 'like', '%'.$products[$i]['domain']['domain'])->count(DB::raw('DISTINCT msg_to'));
            $products[$i]['used'] = $used_count;
            $products[$i]['free'] = $products[$i]['alloweduser'] - $used_count;
            if ($products[$i]['free'] < 0)
                $products[$i]['free'] = 0;
        }

        return view('products')->with([
            'product_array' => $products,
            'customer_array' => $customers,
            'selected_customer_id' => $selected_customer_id
        ]);

    }

    public function showProductAddPage() {
        $customer_id = request('id');
        $customer_array = Customer::get();
        $domain_array = Domain::where('customer_id', $customer_id)->get();
        return view('product_add')->with([
            'customer_array' => $customer_array,
            'domain_array' => $domain_array,
            'selected_customer_id' => $customer_id,
        ]);
    }

    public function addProduct() {
        $customer_id = request('customer-id');
        $domain_id = request('domain-id');
        $product_name = request('product-name');
        $allowed_users = request('allowed-users');

        $rule = [
            'customer-id' => 'required',
            'domain-id' => 'required',
            'product-name' => 'required',
            'allowed-users' => 'required|numeric',
        ];
        $custom_message = [
            'customer-id.required' => 'You must select customer.',
            'domain-id.required' => 'You must select Domain.',
            'product-name.required' => 'The product name field is required.',
            'allowed-users.required' => 'The allowed users field is required.',
            'allowed-users.numeric' => 'The allowed users field must be number.',
        ];

        request()->validate($rule, $custom_message);

        if (isset($customer_id) && isset($domain_id)) {
            $new_product = new Product();
            $new_product->customer_id = $customer_id;
            $new_product->domain_id = $domain_id;
            $new_product->name = $product_name;
            $new_product->alloweduser = $allowed_users;
            $new_product->save();
            return back()
                ->with('success', 'You have successfully added new product.');
        }
        return back()
            ->with('fail', 'Something went wrong.');
    }

    public function showProductEditPage() {
        $id = request('id');
        if (isset($id)) {
            $product = Product::where('id', $id)->first();
            if (isset($product)) {
                $customer_array = Customer::get();
                $domain_array = Domain::where('customer_id', $product->customer_id)->get();
                return view('product_edit')->with([
                    'customer_array' => $customer_array,
                    'domain_array' => $domain_array,
                    'product' => $product
                ]);
            }
        }
        return back();
    }

    public function editProduct() {
        $id = request('id');
        $customer_id = request('customer-id');
        $domain_id = request('domain-id');
        $product_name = request('product-name');
        $allowed_users = request('allowed-users');

        $rule = [
            'customer-id' => 'required',
            'domain-id' => 'required',
            'product-name' => 'required',
            'allowed-users' => 'required|numeric',
        ];
        $custom_message = [
            'customer-id.required' => 'You must select customer.',
            'domain-id.required' => 'You must select Domain.',
            'product-name.required' => 'The product name field is required.',
            'allowed-users.required' => 'The allowed users field is required.',
            'allowed-users.numeric' => 'The allowed users field must be number.',
        ];

        request()->validate($rule, $custom_message);

        if (Product::where([
                ['id', '<>', $id],
                ['customer_id', $customer_id],
                ['name', $product_name]
            ])->count() > 0) {
            return back()
                ->with('fail', 'This product name is already used.');
        }

        Product::where('id', $id)->update([
            'customer_id' => $customer_id,
            'domain_id' => $domain_id,
            'name' => $product_name,
            'alloweduser' => $allowed_users
        ]);

        return back()
            ->with('success', 'You have successfully updated product.');
    }

    public function deleteProduct()
    {
        $id = request('id');
        Product::where('id', $id)->delete();

        return Utils::makeResponse();
    }

    public function showStatisticsPage() {

        $date_from = request('date_from');
        $date_to = request('date_to');
        $stats_type = request('stats_type');

        $initial_clause = '(';
        $domains = Domain::where('customer_id', session()->get('user')->id)->get();

        for($i = 0; $i < count($domains); $i ++) {
            $initial_clause .= '`msg_to` like \'%'.$domains[$i]['domain'].'\' ';
            if ($i < count($domains) - 1) {
                $initial_clause .= 'OR ';
            }
        }
        $initial_clause .= ')';


        $result = array();
        $where_date_clause = '';
        if(isset($date_from))
            $where_date_clause .= 'AND `timestamp` >= \''. $date_from .'\'';
        else $date_from = '';
        if(isset($date_to))
            $where_date_clause .= 'AND `timestamp` <= \''. $date_to .'\'';
        else $date_to = '';

        $action_array = ['sent', 'spam', 'attachment', 'virus'];

        if(isset($stats_type)) {

            // Day
            if($stats_type == 1) {

                $pre_result = array();
                for($i = 0; $i < count($action_array); $i ++) {
                    $query = 'SELECT 
                                SUBSTRING( `timestamp`, 1, 10 ) time, 
                                count( * ) val
                            FROM
                                `logs` 
                            WHERE'.$initial_clause.
                        'AND action = \''.$action_array[$i].'\''.$where_date_clause.
                        'GROUP BY
                                SUBSTRING( `timestamp`, 1, 10 )';
                    $pre_result[$i] = DB::select($query);
                }

                $query = 'SELECT
                                SUBSTRING( `timestamp`, 1, 10 ) time 
                            FROM
                                `logs`
                            WHERE'.$initial_clause.$where_date_clause.'
                            GROUP BY
                            SUBSTRING( `timestamp`, 1, 10 )';
                $result1 = DB::select($query);

                $cnt = 0;
                foreach ($result1 as $one) {
                    $result[$cnt++] = array('time' => $one->time);
                }

                for($i = 0; $i < count($pre_result); $i ++) {
                    for ($j = 0; $j < count($result); $j ++) {
                        foreach ($pre_result[$i] as $pre_result_one) {
                            if ($result[$j]['time'] == $pre_result_one->time) {
                                $result[$j][$action_array[$i]] = $pre_result_one->val;
                            }
                        }
                    }
                }

            } else if ($stats_type == 2) {
                //Month

                $pre_result = array();
                for($i = 0; $i < count($action_array); $i ++) {
                    $query = 'SELECT 
                                SUBSTRING( `timestamp`, 1, 7 ) time, 
                                count( * ) val
                            FROM
                                `logs` 
                            WHERE'.$initial_clause.
                        'AND action = \''.$action_array[$i].'\''.$where_date_clause.
                        'GROUP BY
                                SUBSTRING( `timestamp`, 1, 7 )';
                    $pre_result[$i] = DB::select($query);
                }

                $query = 'SELECT
                                SUBSTRING( `timestamp`, 1, 7 ) time 
                            FROM
                                `logs`
                            WHERE'.$initial_clause.$where_date_clause.'
                            GROUP BY
                            SUBSTRING( `timestamp`, 1, 7 )';
                $result1 = DB::select($query);

                $cnt = 0;
                foreach ($result1 as $one) {
                    $result[$cnt++] = array('time' => $one->time);
                }

                for($i = 0; $i < count($pre_result); $i ++) {
                    for ($j = 0; $j < count($result); $j ++) {
                        foreach ($pre_result[$i] as $pre_result_one) {
                            if ($result[$j]['time'] == $pre_result_one->time) {
                                $result[$j][$action_array[$i]] = $pre_result_one->val;
                            }
                        }
                    }
                }
            } else if ($stats_type == 3) {
                // Year

                $pre_result = array();
                for($i = 0; $i < count($action_array); $i ++) {
                    $query = 'SELECT 
                                SUBSTRING( `timestamp`, 1, 4 ) time, 
                                count( * ) val
                            FROM
                                `logs` 
                            WHERE'.$initial_clause.
                        'AND action = \''.$action_array[$i].'\''.$where_date_clause.
                        'GROUP BY
                                SUBSTRING( `timestamp`, 1, 4 )';
                    $pre_result[$i] = DB::select($query);
                }

                $query = 'SELECT
                                SUBSTRING( `timestamp`, 1, 4 ) time 
                            FROM
                                `logs`
                            WHERE'.$initial_clause.$where_date_clause.'
                            GROUP BY
                            SUBSTRING( `timestamp`, 1, 4 )';
                $result1 = DB::select($query);

                $cnt = 0;
                foreach ($result1 as $one) {
                    $result[$cnt++] = array('time' => $one->time);
                }

                for($i = 0; $i < count($pre_result); $i ++) {
                    for ($j = 0; $j < count($result); $j ++) {
                        foreach ($pre_result[$i] as $pre_result_one) {
                            if ($result[$j]['time'] == $pre_result_one->time) {
                                $result[$j][$action_array[$i]] = $pre_result_one->val;
                            }
                        }
                    }

                }
            } else {
                // Week

                $pre_result = array();
                for($i = 0; $i < count($action_array); $i ++) {
                    $query = 'SELECT 
                                YEARWEEK( `timestamp`) time, 
                                count( * ) val
                            FROM
                                `logs` 
                            WHERE'.$initial_clause.
                        'AND action = \''.$action_array[$i].'\''.$where_date_clause.
                        'GROUP BY
                                YEARWEEK( `timestamp`)';
                    $pre_result[$i] = DB::select($query);
                }

                $query = 'SELECT
                                YEARWEEK( `timestamp`) time 
                            FROM
                                `logs`
                            WHERE'.$initial_clause.$where_date_clause.'
                            GROUP BY
                            YEARWEEK( `timestamp`)';
                $result1 = DB::select($query);

                $cnt = 0;
                foreach ($result1 as $one) {
                    $result[$cnt++] = array('time' => $one->time);
                }

                for($i = 0; $i < count($pre_result); $i ++) {
                    for ($j = 0; $j < count($result); $j ++) {
                        foreach ($pre_result[$i] as $pre_result_one) {
                            if ($result[$j]['time'] == $pre_result_one->time) {
                                $result[$j][$action_array[$i]] = $pre_result_one->val;
                            }
                        }
                    }
                }
                for ($j = 0; $j < count($result); $j ++) {
                    $result[$j]['time'] = substr($result[$j]['time'], 0, 4) . "-" .
                        substr($result[$j]['time'], 4, 2);
                }
            }
        } else $stats_type = 0;

        return view('statistics')->with([
            'result' => $result,
            'stats_type' => $stats_type,
            'date_from' => $date_from,
            'date_to' => $date_to,
        ]);
    }

    public function showSearchPage() {

        $send_from = request('send_from');
        $send_to = request('send_to');
        $date_from = request('date_from');
        $date_to = request('date_to');
        $show_sent = request('show_sent');
        $show_spam = request('show_spam');
        $show_attachment = request('show_attachment');
        $show_virus = request('show_virus');

        $initial_clause = array();
        $domains = Domain::where('customer_id', session()->get('user')->id)->get();
        foreach ($domains as $v) {
            $initial_clause[] = ['msg_to', 'like', '%'.$v['domain']];
        }

        $search_clause = array();
        if (isset($send_from))
            $search_clause[] = array('msg_from', 'like', "%$send_from%");
        else $send_from = '';

        if (isset($send_to))
            $search_clause[] = array('msg_to', 'like', "%$send_to%");
        else $send_to = '';

        if (isset($date_from))
            $search_clause[] = ['timestamp', '>=', "$date_from"];
        else $date_from = '';

        if (isset($date_to))
            $search_clause[] = ['timestamp', '<=', "$date_to"." 23:59:59"];
        else $date_to = '';

        $message_type_clause = array();
        if (isset($show_sent) && $show_sent == 'on')
            $message_type_clause[] = ['action', 'sent'];
        else $show_sent = 'off';

        if (isset($show_spam) && $show_spam == 'on')
            $message_type_clause[] = ['action', 'spam'];
        else $show_spam = 'off';

        if (isset($show_attachment) && $show_attachment == 'on')
            $message_type_clause[] = ['action', 'attachment'];
        else $show_attachment = 'off';

        if (isset($show_virus) && $show_virus == 'on')
            $message_type_clause[] = ['action', 'virus'];
        else $show_virus = 'off';

        if ($send_from == '' && $send_to == '' && $date_from == '' && $date_to == '' && $show_sent == 'off' && $show_spam == 'off'
        && $show_attachment == 'off' && $show_virus == 'off') {
            $search_result = array();
        }
        else
            $search_result = Log::where($search_clause)->
        where(function ($query) use ($initial_clause) {
            if (count($initial_clause) > 0) {
                $query->where([$initial_clause[0]]);
                for ($i = 1; $i < count($initial_clause); $i++) {
                    $query->orwhere([$initial_clause[$i]]);
                }
            }
        })->where(function ($query) use ($message_type_clause) {
            if (count($message_type_clause) > 0) {
                $query->where([$message_type_clause[0]]);
                for ($i = 1; $i < count($message_type_clause); $i++) {
                    $query->orwhere([$message_type_clause[$i]]);
                }
            }
        })->orderBy('timestamp', 'desc')->get();

        return view('search')->with([
            'search_result' => $search_result,
            'send_from' => $send_from,
            'send_to' => $send_to,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'show_sent' => $show_sent,
            'show_spam' => $show_spam,
            'show_attachment' => $show_attachment,
            'show_virus' => $show_virus,
        ]);
    }

    public function showWhitelistPage() {
        $current_user_id = session()->get('user')->id;
        $whitelist_arry = array();
        if (isset($current_user_id)) {
            $whitelist_arry = Whitelist::where('customer_id', $current_user_id)->get();
        }
        return view('whitelist')->with([
            'whitelist_array' => $whitelist_arry
        ]);
    }

    public function showAddWhitelistPage() {
        $domain_array = Domain::where('customer_id', session()->get('user')->id)->get();
        return view('whitelist_add')->with([
            'domain_array' => $domain_array
        ]);
    }

    public function addWhitelist()
    {
        $current_user_id = session()->get('user')->id;
        $from = request('from-address');
        $rcpt = request('rcpt');
        $domain_id = request('domain');

        $rule = [
            'domain' => 'required',
        ];
        $custom_message = [
            'domain.required' => 'You must select Domain.',
        ];

        if (isset($from)) {
            if (count(explode('@', $from)) < 2)
                return back()->with('fail', 'The From Address must be a valid email address or domain. (For only domain, please put @ in front of domain name.)');

            $rule['from-address'] = 'required|regex:/^[-A-Za-z0-9_.@]+$/|max:64';
            $custom_message['from-address.regex'] = 'The From Address must be a valid email address.';
            $custom_message['from-address.max'] = 'The From Address may not be greater than 64 characters.';
        }
        if (isset($rcpt)) {
            $rule['rcpt'] = 'regex:/^[-A-Za-z0-9_.]+$/|max:64';
            $custom_message['rcpt.regex'] = 'The To Address format is invalid.';
            $custom_message['rcpt.max'] = 'The To Address may not be greater than 64 characters.';
        }
        request()->validate($rule, $custom_message);

        for ($i = 0; $i < count($domain_id); $i ++) {
            $domain = Domain::where('id', $domain_id[$i])->first();
            if(isset($domain)) {
                $domain_name = $domain->domain;
                if (isset($rcpt) && $rcpt != '') {
                    $rcpt .= '@' . $domain_name;
                } else $rcpt = $domain_name;
            } else {
                return back()->with('fail', 'Currently selected domain is not correct.');
            }

            if (!isset($from)) $from = '';

            // Check if already exist in blacklist or whitelist
            if (Blacklist::where([
                    ['customer_id', $current_user_id],
                    ['from', $from],
                    ['rcpt', $rcpt]
                ])->count() > 0) {
                return back()
                    ->with([
                        'fail' => 'Sender already in Blacklist.',
                        'data' => 'From Address: ' . $from . " -> To Address: " . $rcpt,
                    ]);
            }

            if (Whitelist::where([
                    ['customer_id', $current_user_id],
                    ['from', $from],
                    ['rcpt', $rcpt]
                ])->count() > 0) {
                return back()
                    ->with([
                        'fail' => 'Sender already in Whitelist.',
                        'data' => 'From Address: ' . $from . " -> To Address: " . $rcpt,
                    ]);
            }

            $whitelist = new Whitelist();
            $whitelist->customer_id = $current_user_id;
            $whitelist->from = $from;
            $whitelist->rcpt = $rcpt;
            $whitelist->is_enabled = 1;
            $whitelist->save();

        }

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return back()
            ->with('success', 'You have successfully add new whitelist.');
    }

    public function addSenderFromSearchResult() {
        $msg_id = request('id');
        $type = request('type');

        if (isset($msg_id)) {
            $log_msg = Log::where('msg_id', "$msg_id")->first();

            if (isset($log_msg)) {
                $sender = $log_msg->msg_from;
                $tmp_1 = explode('<', $sender);

                if (count($tmp_1) > 1){
                    $tmp_2 = explode('>', $tmp_1[1]);

                    if (count($tmp_2) > 1) {
                        $sender_email = $tmp_2[0];
                        $customer_id = session()->get('user')->id;
                        $domain_list = Domain::where('customer_id', $customer_id)->get();

                        $from = '';
                        if ($type == 1 || $type == 3) {
                            $from = $sender_email;
                        } else if ($type == 2 || $type == 4) {
                            $from = '@' . explode('@', $sender_email)[1];
                        }

                        for ($i = 0; $i < count($domain_list); $i ++) {

                            $rcpt = $domain_list[$i]['domain'];
                            //Check if already exist
                            if (Blacklist::where([
                                    ['customer_id', $customer_id],
                                    ['from', $from],
                                    ['rcpt', $rcpt]
                                ])->count() > 0 ||
                                Whitelist::where([
                                    ['customer_id', $customer_id],
                                    ['from', $from],
                                    ['rcpt', $rcpt]
                                ])->count() > 0) {
                                continue;
                            }

                            $whitelist = new Whitelist();
                            $whitelist->customer_id = $customer_id;
                            $whitelist->from = $from;
                            $whitelist->rcpt = $rcpt;
                            $whitelist->is_enabled = 1;
                            $whitelist->save();

                            $blacklist = new Blacklist();
                            $blacklist->customer_id = $customer_id;
                            $blacklist->from = $from;
                            $blacklist->rcpt = $rcpt;
                            $blacklist->is_enabled = 1;
                            $blacklist->save();
                        }
                    }
                }
            }
        }
        return Utils::makeResponse();
    }

    public function showEditWhitelistPage() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;
        $domain_array = Domain::where('customer_id', session()->get('user')->id)->get();

        if (isset($id) && isset($current_user_id)) {
            $result = Whitelist::where([
                ['id', $id],
                ['customer_id', $current_user_id],
            ])->first();
            if(isset($result)) {
                if (count(explode("@", $result->rcpt)) > 1 ) {
                    $domain_name = explode("@", $result->rcpt)[1];
                    $rcpt = explode("@", $result->rcpt)[0];
                } else {
                    $domain_name = explode("@", $result->rcpt)[0];
                    $rcpt = '';
                }

                $domain = Domain::where([
                    ['customer_id', $current_user_id],
                    ['domain', $domain_name],
                ])->first();

                if (isset($domain))
                    $domain_id = $domain->id;
                else return back()->with('fail', 'Something went wrong.');

                return view('whitelist_edit')->with([
                    'whitelist' => $result,
                    'rcpt' => $rcpt,
                    'domain_id' => $domain_id,
                    'domain_array' => $domain_array
                ]);
            }
        }
        return back();
    }

    public function editWhitelist()
    {
        $id = request('id');
        $current_user_id = session()->get('user')->id;
        $from = request('from-address');
        $rcpt = request('rcpt');
        $domain_id = request('domain');

        $rule = [
            'domain' => 'required',
        ];
        $custom_message = [
            'domain.required' => 'You must select Domain.',
        ];

        if (isset($from)) {
            if (count(explode('@', $from)) < 2)
                return back()->with('fail', 'The From Address must be a valid email address or domain. (For only domain, please put @ in front of domain name.)');

            $rule['from-address'] = 'required|regex:/^[-A-Za-z0-9_.@]+$/|max:64';
            $custom_message['from-address.regex'] = 'The From Address must be a valid email address.';
            $custom_message['from-address.max'] = 'The From Address may not be greater than 64 characters.';
        }
        if (isset($rcpt)) {
            $rule['rcpt'] = 'regex:/^[-A-Za-z0-9_.]+$/|max:64';
            $custom_message['rcpt.regex'] = 'The To Address format is invalid.';
            $custom_message['rcpt.max'] = 'The To Address may not be greater than 64 characters.';
        }
        request()->validate($rule, $custom_message);

        $domain = Domain::where('id', $domain_id)->first();
        if(isset($domain)) {
            $domain_name = $domain->domain;
            if ($rcpt != '') {
                $rcpt .= '@' . $domain_name;
            } else $rcpt = $domain_name;
        } else {
            return back()->with('fail', 'Currently selected domain is not correct.');
        }

        if (!isset($from)) $from = '';

        // Check if already exist in blacklist or whitelist
        if (Blacklist::where([
                ['customer_id', $current_user_id],
                ['from', $from],
                ['rcpt', $rcpt],
                ['id', '!=' ,$id],
            ])->count() > 0) {
            return back()
                ->with('fail', 'Sender already in Blacklist.');
        }

        if (Whitelist::where([
                ['customer_id', $current_user_id],
                ['from', $from],
                ['rcpt', $rcpt],
                ['id', '!=' ,$id],
            ])->count() > 0) {
            return back()
                ->with('fail', 'Sender already in Whitelist.');
        }

        $original_rcpt = Whitelist::where('id', $id)->first()->rcpt;
        Whitelist::where('id', $id)->update([
            'from' => $from,
            'rcpt' => $rcpt
        ]);

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return back()
            ->with('success', 'You have successfully update whitelist.');
    }

    public function deleteWhitelist() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;

        if (isset($id) && Whitelist::where([
            ['id', $id],
            ['customer_id', $current_user_id],
            ])->count() > 0) {
            $rcpt = Whitelist::where('id', $id)->first()->rcpt;
            Whitelist::where('id', $id)->delete();

            $this->saveBlackAndWhitelistToFile();
            $this->saveWhitelistRcptToFile();

            return Utils::makeResponse();
        }

        return Utils::makeResponse([], 'Failed');
    }

    public function toggleWhitelistEnable() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;

        if (isset($id) && Whitelist::where([
            ['id', $id],
            ['customer_id', $current_user_id],
            ])->count() > 0) {

            $enable_flag = Whitelist::where('id', $id)->first()->is_enabled;

            Whitelist::where('id', $id)->update([
                'is_enabled' => 1 - $enable_flag,
            ]);

        }

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return Utils::makeResponse([], 'Failed');
    }

    public function showBlacklistPage() {
        $current_user_id = session()->get('user')->id;
        $blacklist_arry = array();
        if (isset($current_user_id)) {
            $blacklist_arry = Blacklist::where('customer_id', $current_user_id)->get();
        }
        return view('blacklist')->with([
            'blacklist_array' => $blacklist_arry
        ]);
    }

    public function showAddBlacklistPage() {
        $domain_array = Domain::where('customer_id', session()->get('user')->id)->get();
        return view('blacklist_add')->with([
            'domain_array' => $domain_array
        ]);
    }

    public function addBlacklist()
    {
        $current_user_id = session()->get('user')->id;
        $from = request('from-address');
        $rcpt = request('rcpt');
        $domain_id = request('domain');

        $rule = [
            'domain' => 'required',
        ];
        $custom_message = [
            'domain.required' => 'You must select Domain.',
        ];

        if (isset($from)) {
            if (count(explode('@', $from)) < 2)
                return back()->with('fail', 'The From Address must be a valid email address or domain. (For only domain, please put @ in front of domain name.)');

            $rule['from-address'] = 'required|regex:/^[-A-Za-z0-9_.@]+$/|max:64';
            $custom_message['from-address.regex'] = 'The From Address must be a valid email address.';
            $custom_message['from-address.max'] = 'The From Address may not be greater than 64 characters.';
        }
        if (isset($rcpt)) {
            $rule['rcpt'] = 'regex:/^[-A-Za-z0-9_.]+$/|max:64';
            $custom_message['rcpt.regex'] = 'The To Address format is invalid.';
            $custom_message['rcpt.max'] = 'The To Address may not be greater than 64 characters.';
        }
        request()->validate($rule, $custom_message);

        for ($i = 0; $i < count($domain_id); $i ++) {
            $domain = Domain::where('id', $domain_id[$i])->first();
            if (isset($domain)) {
                $domain_name = $domain->domain;
                if ($rcpt != '') {
                    $rcpt .= '@' . $domain_name;
                } else $rcpt = $domain_name;
            } else {
                return back()->with('fail', 'Currently selected domain is not correct.');
            }

            if (!isset($from)) $from = '';

            // Check if already exist in blacklist or whitelist
            if (Blacklist::where([
                    ['customer_id', $current_user_id],
                    ['from', $from],
                    ['rcpt', $rcpt]
                ])->count() > 0) {
                return back()
                    ->with([
                        'fail' => 'Sender already in Blacklist.',
                        'data' => 'From Address: ' . $from . " -> To Address: " . $rcpt,
                    ]);
            }

            if (Whitelist::where([
                    ['customer_id', $current_user_id],
                    ['from', $from],
                    ['rcpt', $rcpt]
                ])->count() > 0) {
                return back()
                    ->with([
                        'fail' => 'Sender already in Whitelist.',
                        'data' => 'From Address: ' . $from . " -> To Address: " . $rcpt,
                    ]);
            }

            $blacklist = new Blacklist();
            $blacklist->customer_id = $current_user_id;
            $blacklist->from = $from;
            $blacklist->rcpt = $rcpt;
            $blacklist->is_enabled = 1;
            $blacklist->save();
        }

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return back()
            ->with('success', 'You have successfully add new blacklist.');
    }

    public function showEditBlacklistPage() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;
        $domain_array = Domain::where('customer_id', session()->get('user')->id)->get();

        if (isset($id) && isset($current_user_id)) {
            $result = Blacklist::where([
                ['id', $id],
                ['customer_id', $current_user_id],
            ])->first();
            if(isset($result)) {

                if (count(explode("@", $result->rcpt)) > 1 ) {
                    $domain_name = explode("@", $result->rcpt)[1];
                    $rcpt = explode("@", $result->rcpt)[0];
                } else {
                    $domain_name = explode("@", $result->rcpt)[0];
                    $rcpt = '';
                }

                $domain = Domain::where([
                    ['customer_id', $current_user_id],
                    ['domain', $domain_name],
                ])->first();

                if (isset($domain))
                    $domain_id = $domain->id;
                else return back()->with('fail', 'Something went wrong.');

                return view('blacklist_edit')->with([
                    'blacklist' => $result,
                    'rcpt' => $rcpt,
                    'domain_id' => $domain_id,
                    'domain_array' => $domain_array
                ]);
            }
        }
        return back();
    }

    public function editBlacklist()
    {
        $id = request('id');
        $current_user_id = session()->get('user')->id;
        $from = request('from-address');
        $rcpt = request('rcpt');
        $domain_id = request('domain');

        $rule = [
            'domain' => 'required',
        ];
        $custom_message = [
            'domain.required' => 'You must select Domain.',
        ];

        if (isset($from)) {
            if (count(explode('@', $from)) < 2)
                return back()->with('fail', 'The From Address must be a valid email address or domain. (For only domain, please put @ in front of domain name.)');

            $rule['from-address'] = 'required|regex:/^[-A-Za-z0-9_.@]+$/|max:64';
            $custom_message['from-address.regex'] = 'The From Address must be a valid email address.';
            $custom_message['from-address.max'] = 'The From Address may not be greater than 64 characters.';
        }
        if (isset($rcpt)) {
            $rule['rcpt'] = 'regex:/^[-A-Za-z0-9_.]+$/|max:64';
            $custom_message['rcpt.regex'] = 'The To Address format is invalid.';
            $custom_message['rcpt.max'] = 'The To Address may not be greater than 64 characters.';
        }
        request()->validate($rule, $custom_message);

        $domain = Domain::where('id', $domain_id)->first();
        if(isset($domain)) {
            $domain_name = $domain->domain;
            if ($rcpt != '') {
                $rcpt .= '@' . $domain_name;
            } else $rcpt = $domain_name;
        } else {
            return back()->with('fail', 'Currently selected domain is not correct.');
        }

        if (!isset($from)) $from = '';

        // Check if already exist in blacklist or whitelist
        if (Blacklist::where([
                ['customer_id', $current_user_id],
                ['from', $from],
                ['rcpt', $rcpt],
                ['id', '!=' ,$id],
            ])->count() > 0) {
            return back()
                ->with('fail', 'Sender already in Blacklist.');
        }

        if (Whitelist::where([
                ['customer_id', $current_user_id],
                ['from', $from],
                ['rcpt', $rcpt],
                ['id', '!=' ,$id],
            ])->count() > 0) {
            return back()
                ->with('fail', 'Sender already in Whitelist.');
        }

        Blacklist::where('id', $id)->update([
            'from' => $from,
            'rcpt' => $rcpt
        ]);

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return back()
            ->with('success', 'You have successfully update blacklist.');
    }

    public function deleteBlacklist() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;

        if (isset($id) && Blacklist::where([
                ['id', $id],
                ['customer_id', $current_user_id],
            ])->count() > 0) {
            Blacklist::where('id', $id)->delete();

            $this->saveBlackAndWhitelistToFile();
            $this->saveWhitelistRcptToFile();

            return Utils::makeResponse();
        }

        return Utils::makeResponse([], 'Failed');
    }

    public function toggleBlacklistEnable() {
        $id = request('id');
        $current_user_id = session()->get('user')->id;

        if (isset($id) && Blacklist::where([
                ['id', $id],
                ['customer_id', $current_user_id],
            ])->count() > 0) {

            $enable_flag = Blacklist::where('id', $id)->first()->is_enabled;

            Blacklist::where('id', $id)->update([
                'is_enabled' => 1 - $enable_flag,
            ]);
        }

        $this->saveBlackAndWhitelistToFile();
        $this->saveWhitelistRcptToFile();

        return Utils::makeResponse([], 'Failed');
    }

    function saveBlackAndWhitelistToFile() {
        $black_list = Blacklist::where('is_enabled', 1)->get();
        $white_list = Whitelist::where([
            ['is_enabled', 1],
            ['from','!=' ,''],
        ])->get();

        $content = "#blacklist\n\n";
        foreach ($black_list as $v) {
            $rule_id = $this->generateRandomString() . $v->id . 'b';
            $content .= $rule_id . " {\n";
            $content .= "from = \"" . $v->from . "\";\n";

            if (count(explode('@', $v->rcpt)) > 1 ) {
                $content .= "rcpt = \"" . $v->rcpt . "\";\n";
            } else
                $content .= "rcpt = \"" . '@' . $v->rcpt . "\";\n";

            $content .= "apply {\nactions {\nreject = -10;\n}\n}\n}\n\n";
        }

        $content .= "#whitelist\n\n";
        foreach ($white_list as $v) {
            $rule_id = $this->generateRandomString() . $v->id . 'w';
            $content .= $rule_id . " {\n";
            $content .= "from = \"" . $v->from . "\";\n";
            if (count(explode('@', $v->rcpt)) > 1 ) {
                $content .= "rcpt = \"" . $v->rcpt . "\";\n";
            } else
                $content .= "rcpt = \"" . '@' . $v->rcpt . "\";\n";

            $content .= "want_spam = yes;\n";
            $content .= "}\n\n";
        }

        $myfile = fopen("white-and-blacklist-settings.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
    }

    function saveWhitelistRcptToFile() {
        $white_list_rcpt = Whitelist::where([
            ['from', ''],
            ['is_enabled', 1],
        ])->get();
        $content = "#whitelist-recipients\n\n";
        foreach ($white_list_rcpt as $v) {
            $content .= $v->rcpt . "\n";
        }
        $myfile = fopen("whitelist-recipients.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
    }

    function generateRandomString($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
