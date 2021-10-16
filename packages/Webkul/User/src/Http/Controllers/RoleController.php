<?php

namespace Webkul\User\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

class RoleController extends Controller
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * RoleRepository object
     *
     * @var \Webkul\User\Repositories\RoleRepository
     */
    protected $roleRepository;

    /**
     * AdminRepository object
     *
     * @var \Webkul\User\Repositories\AdminRepository
     */
    protected $adminRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\User\Repositories\RoleRepository  $roleRepository
     * @param  \Webkul\User\Repositories\AdminRepository  $adminRepository
     * @return void
     */
    public function __construct(RoleRepository $roleRepository, AdminRepository $adminRepository)
    {
        $this->middleware('admin');

        $this->roleRepository = $roleRepository;
        $this->adminRepository = $adminRepository;

        $this->_config = request('_config');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view($this->_config['view']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'name'            => 'required',
            'permission_type' => 'required',
        ]);

        Event::dispatch('user.role.create.before');

        $role = $this->roleRepository->create(request()->all());

        Event::dispatch('user.role.create.after', $role);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'Role']));

        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $role = $this->roleRepository->findOrFail($id);

        return view($this->_config['view'], compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->validate(request(), [
            'name'            => 'required',
            'permission_type' => 'required',
        ]);

        Event::dispatch('user.role.update.before', $id);

        if (request()->permission_type != 'all'){
            if ($this->roleRepository->findWhere(['permission_type' => 'all'], 'permission_type')->count() > 1){
                $role = $this->roleRepository->update(request()->all(), $id);
                Event::dispatch('user.role.update.after', $role);
                session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Role']));
                if($this->adminRepository->findWhere(['id' => auth()->guard('admin')->user()->id, 'role_id' => $id], 'role_id')->count() > 0){
                    auth()->guard('admin')->logout();
                }
            }
            else{
                session()->flash('error', trans('This is last Role with ALL permission.'));
            }
        }
        else{
            $role = $this->roleRepository->update(request()->all(), $id);
            Event::dispatch('user.role.update.after', $role);
            session()->flash('success', trans('admin::app.response.update-success', ['name' => 'Role']));
        }

        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = $this->roleRepository->findOrFail($id);

        if ($role->admins->count() >= 1) {
            session()->flash('error', trans('admin::app.response.being-used', ['name' => 'Role', 'source' => 'Admin User']));
        } elseif($this->roleRepository->count() == 1) {
            session()->flash('error', trans('admin::app.response.last-delete-error', ['name' => 'Role']));
        } elseif($this->roleRepository->findWhere(['permission_type' => 'all'], 'permission_type')->count() > 1) {
            session()->flash('error', trans('This is last Role with ALL permission.'));
        } else {
            try {
                Event::dispatch('user.role.delete.before', $id);

                $this->roleRepository->delete($id);

                Event::dispatch('user.role.delete.after', $id);

                session()->flash('success', trans('admin::app.response.delete-success', ['name' => 'Role']));

                return response()->json(['message' => true], 200);
            } catch(\Exception $e) {
                session()->flash('error', trans('admin::app.response.delete-failed', ['name' => 'Role']));
            }
        }

        return response()->json(['message' => false], 400);
    }
}