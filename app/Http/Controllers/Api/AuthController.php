<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Album;
use Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use App\Jobs\SendRegistrationConfirmation;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    //For User Register
    public function register(Request $request){

        $validator = Validator::make($request->all(),[
            'name'=>'required|string|min:2|max:100',
            'email'=>'required|string|email:rfc,dns|max:100|unique:user',
            'phone'=>'required|numeric',
            'bio'=>'required',
            'password'=>'required|min:5|max:100',
            'confirm_password'=>'required|same:password',
        ]);

        if ($validator->fails()) {
            //return response()->json($validator->errors());
            return response()->json([
                'message'=> 'Validation Fails',
                'errors'=>$validator->errors()
            ]);    
        }
        
        $status = 'active';
        $currentDateTime = date('Y-m-d H:i:s');
        
            //Create User Model for get post data
            $user = User::create([
                'name'=>$request->name,
                'email'=>$request->email,
                'phone'=>$request->phone,
                'bio'=>$request->bio,
                'password'=>Hash::make($request->password),
                'status'=>$status,
                'created'=>$currentDateTime,
                'modified'=>$currentDateTime
            ]);
            //Insert data into Album Table based on user_id
            $userAlbumJsonData = file_get_contents(storage_path('app/user-album.json')); //storage/app root path
            $albumData = json_decode($userAlbumJsonData, true);
            foreach ($albumData as $item) {
                DB::table('album')->insert([
                    'user_id' => $user->id,
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'featured_image' => $item['img'],
                    'date' => $item['date'],
                    'is_featured' => $item['featured'],
                    'created'=>$currentDateTime,
                    'modified'=>$currentDateTime
                ]);
            }//foreach ends here

            //Send Mail to user
            //$userEmail = $request->email;
            //$userEmail = 'jimi.kanoja93@gmail.com';
            //dispatch(new SendRegistrationConfirmation($user));
            $job = (new SendRegistrationConfirmation($user))->onQueue('emails');
            Queue::push($job);

            info('User registered: ' . $user->email);
            //dispatch(new SendEmailJob($userEmail));
            //SendEmailJob::dispatch($userEmail);
            //dd('Send Mail Successfully!');

            return response()->json([
                'message'=> 'You have been registered successfully. Please check your email for the confirmation',
                'data'=>$user
            ],200);
       
            
    }

    //For User Login
    public function login(Request $request){

        $validator = Validator::make($request->all(),[
            'email'=>'required|string|email',
            'password'=>'required|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=> 'Validation Fails',
                'errors'=>$validator->errors()
            ]);    
        }

        $user = User::where('email', $request->email)->first();
        if($user)
        {
            if(Hash::check($request->password,$user->password)){
                //Get token if password and email id match with the DB
                if(!$token = auth()->attempt($validator->validated()))
                {
                    return response()->json(['success'=>false,'msg'=>'Username and Password is incorrect']);
                }
                //$this->respondWithToken($token);

                //$token = $user->createToken('auth-token')->plainTextToken;
                return response()->json([
                    'message'=> 'Login Successful',
                    'access_token'=> $token,
                    'token_type'=>'Bearer',
                    'expires_in'=>auth()->factory()->getTTL()*60,//3600min
                    'data'=>$user,
                ],200); 
            }
            else{
                return response()->json(['success'=>false,'msg'=>'Username and Password is incorrect']);
            }
        }
        else{
            return response()->json(['success'=>false,'msg'=>'Username and Password is incorrect']);
        }
        
    }

    /*protected function respondWithToken($token)
    {
        return response()->json([
            'success'=>true,
            'access_token'=>$token,
            'token_type'=>'Bearer',
            'expires_in'=>auth()->factory()->getTTL()*60,//3600min
        ]);
    }*/

    //For Logout
    public function logout(){
        try{
            auth()->logout();
            return response()->json(['success'=>true,'msg'=>'User logged out!']);
        }catch(\Exception $e){
            return response()->json(['success'=>false,'msg'=>$e->getMessage()]);
        }
    }

    //For getUser Data
    public function getUser(){
        try{
            return response()->json(['success'=>true,'data'=>auth()->user()]);
        }catch(\Exception $e){
            return response()->json(['success'=>false,'msg'=>$e->getMessage()]);
        }
    }

    //For forget Password api method
    public function forgetPassword(Request $request){
         try{

            if($request->email)
            {
                //check email exist or not
                $user = User::where('email',$request->email)->get();
                if(count($user) > 0)
                {
                    $token = Str::random(40);
                    $verificationCode = Str::random(6);
                    $domain = URL::to('http://localhost:3000');
                    $url = $domain.'/reset-password/'.$token;
                    $data['url'] = $url;
                    $data['email'] = $request->email;
                    $data['title'] = 'Password Reset';
                    $data['verificationCode'] = $verificationCode;

                    Mail::send('forgetPasswordMail',['data'=>$data],function($message) use ($data){
                    $message->to($data['email'])->subject($data['title']);
                    });

                    $datetime = Carbon::now()->format('Y-m-d H:i:s');
                    PasswordReset::updateOrCreate(
                        ['email'=>$request->email],
                        [
                            'email' => $request->email,
                            'token' => $token,
                            'verification_code' => $verificationCode,
                            'created_at' => $datetime
                        ]
                        );
                        return response()->json(['success'=>true,'msg'=>'Please check your mail to reset your password.']);
                }
                else{
                    return response()->json(['success'=>false,'msg'=>'User not found!']);
                }
            }
            else{
                return response()->json(['success'=>false,'msg'=>'Please enter email id']);
            }
                
        }catch(\Exception $e){
            return response()->json(['success'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function resetPassword(Request $request){
        try{

                //check token exist or not
                $userToken = PasswordReset::where('token',$request['token'])->first();
                //return $userToken['email'];
                if($userToken)
                {
                    //Check Token expiry time
                    $tokenCreatedTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $userToken['created_at'])->getTimestamp();
                    $expirationTimestamp = $tokenCreatedTimestamp + (config('auth.passwords.users.expire') * 10);
                    $expireTimeVal = $expirationTimestamp < time();
                    if($expireTimeVal){
                        return response()->json([
                            'message'=> 'Link Expired',
                            'errors'=>'Link is expired please resend the link!'
                            //'errors'=>'token created time::'.$tokenCreatedTimestamp.'::expire time::'.$expirationTimestamp.'::expiry value::'.$expireTimeVal
                        ]);
                    }

                    //Check empty field validation
                    $validator = Validator::make($request->all(),[
                        'verification_code'=>'required',
                        'password'=>'required|min:5|max:100',
                        'confirm_password'=>'required|same:password',
                    ]);

                    if ($validator->fails()) {
                        return response()->json([
                            'message'=> 'Validation Fails',
                            'errors'=>$validator->errors()
                        ]);    
                    }

                    //check verification code exist or not
                    $verificationCode = PasswordReset::where('verification_code',$request['verification_code'])->first();
                    if($verificationCode){
                    
                        if($request['password'] == $request['confirm_password'])
                        {
                            //Get user ID
                            $user = User::where('email',$userToken['email'])->first();
                            
                            //update user password with HASH
                            $userPass = User::find($user['id']);
                            $userPass->password = Hash::make($request['password']);
                            $userPass->save();

                            //delete user_token from the PasswordResets table
                            PasswordReset::where('email',$userToken['email'])->delete();
                            //delete verification_code from the PasswordResets table
                            //PasswordReset::where('email',$userToken['email'])->delete();

                            return response()->json(['success'=>true,'msg'=>'Password updated successfully!']);
                        }
                        else{
                            return response()->json(['success'=>false,'msg'=>'Password and Confirm Password must be same']);
                        }
                    
                    }//$verificationCode ends here
                    else{
                        return response()->json(['success'=>false,'msg'=>'Verification code not matched']);
                    }
                }
                else{
                    return response()->json(['success'=>false,'msg'=>'User Token not found!']);
                }
            
        }catch(\Exception $e){
            return response()->json(['success'=>false,'msg'=>$e->getMessage()]);
        }
    }

    //Check USer Password Reset Link is expired
    public function password_expired($email)
    {
        $passwordReset = User::where('email',$email)->first();
        /*$passwordReset = DB::table('password_resets')
            ->where('email', $user->email)
            ->first();*/

        /*if (! $passwordReset) {
            return true;
        }*/

        $tokenCreatedTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $passwordReset->created_at)->getTimestamp();
        $expirationTimestamp = $tokenCreatedTimestamp + (config('auth.passwords.users.expire') * 10);

        $expireTimeVal = $expirationTimestamp < time();
        return $email;


        /*$expiresAt = Carbon::now()->subMinutes(config('auth.passwords.users.expire', 10));
        $tokenCreatedAt = Carbon::createFromTimestamp($this->broker()->getRepository()->getCreationTimestamp($token));
        return $tokenCreatedAt->lt($expiresAt);*/

        /*$user = Auth::user();
        info("Password expired function called",$user);
        $password_updated_at = new Carbon($user->password_updated_at);
        $password_expires_at = $password_updated_at->addDays(config('auth.passwords.users.expire'));

        if ($password_expires_at->isPast()) {
            // Password has expired
            return true;
        } else {
            // Password is still valid
            return false;
        }*/
    }

    //Get USer Album
    public function getUserAlbum(Request $request){
        try{

            if($request->id)
            {
                 //check user id exist or not
                $userAlbum = Album::where('user_id', $request->id)->get();
                //return $userAlbum;
                if($userAlbum)
                {
                    return response()->json(['success'=>true,'data'=>$userAlbum]);
                    
                }
                else{
                    return response()->json(['success'=>false,'msg'=>'User not found!']);
                }
            }
            else
            {
                return response()->json(['success'=>false,'msg'=>'User not found!']);
            }
           
            
        }catch(\Exception $e){
            return response()->json(['success'=>false,'msg'=>$e->getMessage()]);
        }
    }

    //Upload Profile Picture using AWS S3 Bucket
    public function uploadImage(Request $request){

        $s3Client = new S3Client([
            'region' => 'ca-central-1',
            'version' => 'latest',
            'credentials' => [
                'key' => 'AKIA3J7YUBECL6QMY7E5',
                'secret' => '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',
            ],
        ]);

        //log the file data in storgae/laravel.log file and check if file data exists or not
        info($request->all());
        if($request->has('image')){
            $userId = $request->user_id;
            $file = $request->file('image');
            $path = "s3-profile-picture/".time()."_".$file->getClientOriginalName();
            //Upload on S3 bucket
            $storageInfo = \Storage::disk("s3")->put($path, file_get_contents($file)); //it will give true

            if($storageInfo == true){
                //Get Image URL from S3 Bucket
                //$ImagePath = \Storage::disk("s3")->url($path);
                /*$bucketName = 'creativeguild-user-profile';
                $objectKey = "s3-profile-picture/".time()."_".$file->getClientOriginalName();
                $s3 = Storage::disk('s3')->getAdapter()->getClient();
                $url = $s3->getObjectUrl($bucketName, $objectKey);
                */
                $objects = $s3Client->listObjects([
                    'Bucket' => 'creativeguild-user-profile',
                ]);
                $lastObjectKey = end($objects['Contents'])['Key'];
                $s3 = Storage::disk('s3')->getAdapter()->getClient();
                $lastObjectUrl = $s3->getObjectUrl('creativeguild-user-profile', $lastObjectKey);
                //$lastObjectUrl = $s3Client->getObjectUrl('creativeguild-user-profile', $lastObjectKey);
                $url = str_replace('\\', '', $lastObjectUrl);

                //update Profile_picture URL in user table
                $userProfile = User::find($userId);
                $userProfile->profile_picture = $url;
                $userProfile->save();

                //return response
                return response()->json(['success'=>true,'msg'=>$url]);
            }
            else
            {
                return response()->json(['success'=>false,'msg'=>'Image not uploaded']);
            }
            

            
        }
        
    }
}
 