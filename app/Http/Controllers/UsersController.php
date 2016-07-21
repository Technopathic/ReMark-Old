<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use Auth;
use App\User;
use App\Role;
use App\Option;
use App\Subscription;
use \Input;
use \Response;
use \Image;
use \Session;
use \Redirect;

class UsersController extends Controller
{

  public function __construct()
  {
    $this->middleware('jwt.auth');
  }

  public function getUsers(Request $request)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $users = User::join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->get();
      $roles = Role::select('id', 'roleName', 'roleSlug', 'roleDesc', 'roleCount')->get();

      return Response::json(['users' => $users, 'roles' => $roles])->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function editUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $user = User::find($id);

      return Response::json($user)->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function banUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user->id != $options->owner)
      {
        if($user->ban == 0)
        {
          $user->ban = 1;
          $user->save();
          return Response::json(1)->setCallback($request->input('callback'));
        }
        else {
          $user->ban = 0;
          $user->save();
          return Response::json(2)->setCallback($request->input('callback'));
        }
      }
      else {
        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function resetPassword(Request $request, $id) {
    $user = Auth::user();
    if($user->role == 1)
    {
      $user = User::find($id);

      if(!empty($user))
      {
        Mail::send('emails.resetPassword', ['user' => $user], function ($m) use ($user) {
            $m->to($user->email)->subject('Password Reset');
        });

        return Response::json(1)->setCallback($request->input('callback'));
      }
      else {
        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function activateUser(Request $request, $id) {
    $user = Auth::user();
    if($user->role == 1)
    {
      $user = User::find($id);

      if($user->activated == 0)
      {
        $user->activated = 1;
        $user->save();

        return Response::json(1)->setCallback($request->input('callback'));
      }
      elseif($user->activated == 1)
      {
        $user->activated = 0;
        $user->save();

        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function deleteUser(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $user = User::find($id);
      $options = Option::find(1);
      if($user->id != $options->owner)
      {
        $user->delete();
        return Response::json(1)->setCallback($request->input('callback'));
      }
      else {
        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function storeUser(Request $request)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $rules = array(
        'newUserEmail'	        => 	'required',
        'newUserName'			=>	'required',
        'newUserPassword'			=>	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
      } else {

        $email = $request->json('newUserEmail');
        $username = $request->json('newUserName');
        $password = $request->json('newUserPassword');

        $username = preg_replace('/[^A-Z]/i', "" ,$username);
        $sub = substr($username, 0, 2);

        $userCheck = User::where('email', '=', $email)->orWhere('name', '=', $username)->select('email', 'name')->first();

        if(empty($userCheck))
        {

          $role = Role::find(2);

          $user = new User;
          $user->email = $email;
          $user->name = $username;
          $user->password = Hash::make($password);
          $user->displayName = $username;
          $user->avatar = "https://invatar0.appspot.com/svg/".$sub.".jpg?s=100";
          $user->role = $role->roleName;
          $user->activated = 1;

          $user->save();

          $role->roleCount = $role->roleCount + 1;
          $role->save();

          $newUser = User::where('users.id', '=', $user->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.location', 'users.website', 'users.aboutMe', 'users.profileTitle', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.last_login', 'users.created_at', 'roles.roleName')->first();
          return Response::json($newUser)->setCallback($request->input('callback'));

        } else {
          if($userCheck->email == $email)
          {
            return Response::json(2)->setCallback($request->input('callback'));
          }
          elseif($userCheck->name == $username)
          {
            return Response::json(3)->setCallback($request->input('callback'));
          }
        }
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function updateProfile(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $profile = User::find($id);

      $displayName = $request->input('displayName');
      $email = $request->input('email');
      $avatar = $request->file('avatar');
      $password = $request->input('password');
      $confirm = $request->input('confirm');
      $emailReply = $request->input('emailReply');
      $emailDigest = $request->input('emailDigest');

      if($displayName == NULL)
      {
        $displayName = $profile->name;
      }
      else {
        $profile->displayName = $displayName;
      }
      if($email != NULL)
      {
        $profile->email = $email;
      }
      if($emailReply != NULL)
      {
        $profile->website = $emailReply;
      }
      if($emailDigest != NULL)
      {
        $profile->emailDigest = $emailDigest;
      }

      if($avatar != NULL)
      {

        $imageFile = 'storage/media/users/avatars';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $ext = $avatar->getClientOriginalExtension();
        $fileName = str_random(8);
        $avatar->move($imageFile, $fileName.'.'.$ext);
        $avatar = $imageFile.'/'.$fileName.'.'.$ext;

        if (extension_loaded('fileinfo')) {
          $img = Image::make($avatar);

          list($width, $height) = getimagesize($avatar);
          if($width > 200)
          {
            $img->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            if($height > 200)
            {
              $img->crop(200, 200);
            }
          }
          $img->save($avatar);
        }

        $profile->avatar = $avatar;
      }

      if($password != NULL)
      {
        if($password === $confirm)
        {
          $password = Hash::make($password);
          $profile->password = $password;
        } else {
          return Response::json(0)->setCallback($request->input('callback'));
        }
      }

      $profile->save();

      $userData= User::where('users.id', '=', $profile->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.location', 'users.website', 'users.aboutMe', 'users.profileTitle', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->first();
      return Response::json($userData)->setCallback($request->input('callback'));

    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function storeRole(Request $request)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $rules = array(
        'roleName'		=> 	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(0)->setCallback($request->input('callback'));
      } else {

        $roleName = $request->json('roleName');
        $roleDesc = $request->json('roleDesc');
        $roleSlug = preg_replace("/ /","+",$roleName);

        if (Role::where('roleSlug', '=', $roleSlug)->exists()) {
           $roleSlug = $roleSlug.'_'.mt_rand(1, 9999);
        }
        if(empty($roleDesc))
        {
          $roleDesc = "No Description";
        }

        $role = new Role;

        $role->roleName = $roleName;
        $role->roleDesc = $roleDesc;
        $role->roleSlug = $roleSlug;
        $role->roleCount = 0;
        $role->save();

        $roleData = Role::where('id', '=', $role->id)->select('id', 'roleName', 'roleSlug', 'roleDesc', 'roleCount')->first();
        return Response::json($roleData)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function filterRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $role = Role::find($id);
      $users = User::where('users.role', '=', $role->roleName)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.last_login', 'users.created_at', 'roles.roleName')->get();

      return Response::json($users)->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function editRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $role = Role::find($id);

      return Response::json($role)->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function updateRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $rules = array(
        'roleName'		=> 	'required'
      );
      $validator = Validator::make($request->json()->all(), $rules);

      if ($validator->fails()) {
          return Response::json(0)->setCallback($request->input('callback'));
      } else {

        $role = Role::find($id);
        $roleName = $request->json('roleName');
        $roleDesc = $request->json('roleDesc');

        $roleCheck = Role::where('roleName', '=', $roleName)->first();
        if(empty($roleCheck))
        {
          $role->roleName = $roleName;
          $roleSlug = preg_replace("/ /","+",$roleName);
          if (Role::where('roleSlug', '=', $roleSlug)->exists()) {
             $roleSlug = $roleSlug.'+'.mt_rand(1, 9999);
          }
          $role->roleSlug = $roleSlug;
          $role->roleDesc = $roleDesc;
          $role->save();
          if(empty($roleDesc))
          {
            $roleDesc = "No Description";
          }
        }
        return Response::json($role)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function deleteRole(Request $request, $id)
  {
    $user = Auth::user();
    if($user->role == 1)
    {
      $role = Role::find($id);

      if($role->id != 1 && $role->id != 2)
      {
        $users = User::where('role', '=', $role->roleName)->get();
        $newRole = Role::find(2);
        if($users->isEmpty())
        {
          foreach($users as $key => $value)
          {
            $value->role = $newRole->roleName;
            $value->save();
          }
        }
        $role->delete();
        return Response::json(1)->setCallback($request->input('callback'));
      }
      else {
        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function setRole(Request $request, $id)
  {
    $user = Auth::user();

    if($user->role == 1)
    {
      $user = User::find($id);
      $role = $request->json('roleID');

      $roleCheck = Role::where('id', '=', $role)->first();
      if(!empty($roleCheck))
      {
        $option = Option::find(1);
        if($user->id == $option->owner)
        {
          //Role Cannot be changed.
          return Response::json(2)->setCallback($request->input('callback'));
        } else {
          $user->role = $role;
          $user->save();
          //Success
          $userData = User::where('users.id', '=', $user->id)->join('roles', 'users.role', '=', 'roles.id')->select('users.id', 'users.name', 'users.displayName', 'users.email', 'users.location', 'users.website', 'users.aboutMe', 'users.profileTitle', 'users.avatar', 'users.topics', 'users.replies', 'users.role', 'users.activated', 'users.ban', 'users.last_login', 'users.created_at', 'roles.roleName')->first();
          return Response::json($userData)->setCallback($request->input('callback'));
        }
      } else {
        //Role not found
        return Response::json(0)->setCallback($request->input('callback'));
      }
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }

  }

  public function getSubscriptions(Request $request)
  {
    $user = Auth::user();

    if($user->role == 1)
    {
      $subscriptions = Subscription::all();

      return Response::json($subscriptions)->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }

  public function deleteSubscription(Request $request)
  {
    $id = $request->json('id');
    $user = Auth::user();
    if($user->role == 1)
    {
      $subscriber = Subscription::where('id', '=', $id)->first();

      $subscriber->delete();

      return Response::json(1)->setCallback($request->input('callback'));
    } else {
      return Response::json(403)->setCallback($request->input('callback'));
    }
  }
}
