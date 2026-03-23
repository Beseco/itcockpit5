<?php

namespace App\Http\Controllers;

use App\Models\Gruppe;
use App\Models\Stelle;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GruppeService;
use App\Services\PasswordGeneratorService;
use App\Services\UserMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    protected AuditLogger $auditLogger;
    protected PasswordGeneratorService $passwordGenerator;
    protected UserMailService $userMailService;
    protected GruppeService $gruppeService;

    public function __construct(
        AuditLogger $auditLogger,
        PasswordGeneratorService $passwordGenerator,
        UserMailService $userMailService,
        GruppeService $gruppeService,
    ) {
        $this->auditLogger = $auditLogger;
        $this->passwordGenerator = $passwordGenerator;
        $this->userMailService = $userMailService;
        $this->gruppeService = $gruppeService;
    }

    /**
     * Display a listing of users with optional filters.
     */
    public function index(Request $request)
    {
        $this->authorize('base.users.view');

        $search   = $request->input('search', '');
        $gruppeId = $request->input('gruppe_id', '');
        $sortDir  = $request->input('sort', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = User::with(['gruppen', 'roles'])
            ->when($search, fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($request->filled('role'), fn($q) => $q->byRole($request->role))
            ->when($request->filled('active'), function ($q) use ($request) {
                if ($request->active === '1') $q->active();
                elseif ($request->active === '0') $q->where('is_active', false);
            })
            ->when($gruppeId, fn($q) => $q->whereHas('gruppen', fn($q2) => $q2->where('gruppen.id', $gruppeId)));

        $users   = $query->orderBy('name', $sortDir)->paginate(25)->withQueryString();
        $gruppen = Gruppe::orderBy('name')->get();

        return view('users.index', compact('users', 'gruppen', 'search', 'gruppeId', 'sortDir'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('base.users.create');

        $roles   = \Spatie\Permission\Models\Role::with('permissions')->get();
        $gruppen = Gruppe::with('children')->roots()->get();
        return view('users.create', compact('roles', 'gruppen'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('base.users.create');

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'         => ['nullable', 'string', 'min:8'],
            'send_credentials' => ['boolean'],
            'roles'            => ['nullable', 'array'],
            'roles.*'          => ['string', 'exists:roles,name'],
            'is_active'        => ['boolean'],
            'gruppe_ids'       => ['nullable', 'array'],
            'gruppe_ids.*'     => ['integer', 'exists:gruppen,id'],
        ]);

        // Generate password if not provided (Req. 2.2)
        $plaintextPassword = filled($validated['password'] ?? null)
            ? $validated['password']
            : $this->passwordGenerator->generate();

        // Create user – password is auto-hashed via the 'hashed' cast (Req. 2.3)
        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $plaintextPassword,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Assign roles via Spatie (Req. 5.4)
        $user->syncRoles($request->input('roles', []));

        // Assign groups (gruppe_ids) and inherit group roles
        $gruppeIds = $validated['gruppe_ids'] ?? [];
        $user->gruppen()->sync($gruppeIds);
        if (!empty($gruppeIds)) {
            $this->gruppeService->syncUserRoles($user);
        }

        // Send welcome e-mail if checkbox is checked (Req. 2.4, 2.5)
        if ($request->boolean('send_credentials')) {
            $this->userMailService->sendWelcomeMail($user, $plaintextPassword);
        }

        // Plaintext password goes out of scope here – never stored (Req. 2.6)

        // Audit log
        $this->auditLogger->logUserAction('created', $user, [
            'roles'     => $user->getRoleNames(),
            'is_active' => $user->is_active,
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorize('base.users.view');

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorize('base.users.edit');

        $roles   = \Spatie\Permission\Models\Role::with('permissions')->get();
        $gruppen = Gruppe::with('children')->roots()->get();
        $user->load(['gruppen', 'stellen']);
        $stellen = Stelle::with(['stellenbeschreibung', 'gruppe'])
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->orderBy('stellennummer')
            ->get();

        return view('users.edit', compact('user', 'roles', 'gruppen', 'stellen'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('base.users.edit');

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'  => ['nullable', 'confirmed', 'min:8'],
            'roles'        => ['nullable', 'array'],
            'roles.*'      => ['string', 'exists:roles,name'],
            'is_active'    => ['sometimes', 'boolean'],
            'gruppe_ids'   => ['nullable', 'array'],
            'gruppe_ids.*' => ['integer', 'exists:gruppen,id'],
            'stelle_ids'   => ['nullable', 'array'],
            'stelle_ids.*' => ['integer', 'exists:stellen,id'],
        ]);

        // Track changes for audit log
        $changes = [];

        if ($user->name !== $validated['name']) {
            $changes['name'] = ['from' => $user->name, 'to' => $validated['name']];
        }

        if ($user->email !== $validated['email']) {
            $changes['email'] = ['from' => $user->email, 'to' => $validated['email']];
        }

        $newIsActive = $validated['is_active'] ?? $user->is_active;
        if ($user->is_active !== $newIsActive) {
            $changes['is_active'] = ['from' => $user->is_active, 'to' => $newIsActive];
        }

        // Update user
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $newIsActive;

        // Update password if provided
        if (isset($validated['password']) && $validated['password'] !== null) {
            $user->password = Hash::make($validated['password']);
            $changes['password'] = 'updated';
        }

        $user->save();

        // Sync groups first, then roles (groups may add additional roles)
        $gruppeIds = $validated['gruppe_ids'] ?? [];
        $user->gruppen()->sync($gruppeIds);

        // Sync roles via Spatie (Req. 5.4)
        $previousRoles = $user->getRoleNames()->toArray();
        $user->syncRoles($request->input('roles', []));
        // Re-apply group roles on top of directly assigned roles
        if (!empty($gruppeIds)) {
            $this->gruppeService->syncUserRoles($user);
        }
        $newRoles = $user->getRoleNames()->toArray();

        if ($previousRoles !== $newRoles) {
            $changes['roles'] = ['from' => $previousRoles, 'to' => $newRoles];
        }

        // Sync Stellen: unassign old, assign new
        $stelleIds = $validated['stelle_ids'] ?? [];
        Stelle::where('user_id', $user->id)->whereNotIn('id', $stelleIds)->update(['user_id' => null]);
        if (!empty($stelleIds)) {
            Stelle::whereIn('id', $stelleIds)->update(['user_id' => $user->id]);
        }

        // Always log the action, even if no changes (to track update attempts)
        $this->auditLogger->logUserAction('updated', $user, array_merge($changes, [
            'roles' => $user->getRoleNames(),
        ]));

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('base.users.delete');

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->toArray(),
        ];

        $user->delete();

        // Log the action
        $this->auditLogger->log('User', 'User deleted', $userData);

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleActive(User $user)
    {
        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        // Log the action
        $this->auditLogger->logUserAction(
            $user->is_active ? 'activated' : 'deactivated',
            $user,
            [
                'is_active' => [
                    'from' => $oldStatus,
                    'to' => $user->is_active,
                ]
            ]
        );

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('users.index')
            ->with('success', "User {$status} successfully.");
    }
}
