<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
        $this->middleware('module.access:announcements');
    }

    /**
     * Display a listing of announcements.
     */
    public function index(Request $request)
    {
        $this->authorize('announcements.view');

        $perPage = in_array((int) $request->get('per_page', 25), [25, 50, 100, 250]) ? (int) $request->get('per_page', 25) : 25;
        $announcements = Announcement::orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('announcements.index', compact('announcements', 'perPage'));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create()
    {
        $this->authorize('announcements.create');

        return view('announcements.create');
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('announcements.create');

        $validated = $request->validate([
            'type' => ['required', Rule::in(['info', 'maintenance', 'critical'])],
            'message' => ['required', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $announcement = Announcement::create($validated);

        // Log the action
        $this->auditLogger->logAnnouncementAction('created', $announcement);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Display the specified announcement.
     */
    public function show(Announcement $announcement)
    {
        $this->authorize('announcements.view');

        return view('announcements.show', compact('announcement'));
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Announcement $announcement)
    {
        $this->authorize('announcements.edit');

        return view('announcements.edit', compact('announcement'));
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $this->authorize('announcements.edit');

        $validated = $request->validate([
            'type' => ['required', Rule::in(['info', 'maintenance', 'critical'])],
            'message' => ['required', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $announcement->update($validated);

        // Log the action
        $this->auditLogger->logAnnouncementAction('updated', $announcement);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $this->authorize('announcements.delete');

        $announcementData = [
            'id' => $announcement->id,
            'type' => $announcement->type,
            'message' => $announcement->message,
        ];

        $announcement->delete();

        // Log the action
        $this->auditLogger->log('Announcement', 'Announcement deleted', $announcementData);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    /**
     * Mark a critical announcement as fixed.
     */
    public function markAsFixed(Announcement $announcement)
    {
        $announcement->is_fixed = true;
        $announcement->fixed_at = now();
        $announcement->save();

        // Log the action
        $this->auditLogger->logAnnouncementAction('marked as fixed', $announcement);

        return redirect()->back()
            ->with('success', 'Announcement marked as fixed.');
    }
}
