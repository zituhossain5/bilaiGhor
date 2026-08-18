<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use Illuminate\Support\Facades\Storage;

class AdminComplaintController extends Controller
{
    /**
     * Complaint list (Admin panel)
     * AJAX + Pagination supported
     */
    public function index(Request $request)
    {
        // ✅ Pagination (fast load)
        $complaints = Complaint::latest()->paginate(10);

        // ✅ AJAX request হলে শুধু table অংশ return করবে
        if ($request->ajax()) {
            return view('backEnd.complaints.partials.table', compact('complaints'))->render();
        }

        // ✅ Normal page load
        return view('backEnd.complaints.index', compact('complaints'));
    }

    /**
     * Update complaint status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,resolved',
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->status = $request->status;
        $complaint->save();

        // AJAX support থাকলেও redirect safe
        return back()->with('success', 'Complaint status updated successfully');
    }

    public function attachment(Complaint $complaint)
    {
        abort_unless($complaint->image, 404);

        if (Storage::disk('private')->exists($complaint->image)) {
            $extension = pathinfo($complaint->image, PATHINFO_EXTENSION);

            return response()->file(Storage::disk('private')->path($complaint->image), [
                'Content-Disposition' => 'inline; filename="ticket-proof-' . $complaint->id . '.' . $extension . '"',
            ]);
        }

        $legacyPath = public_path($complaint->image);
        abort_unless(is_file($legacyPath), 404);

        return response()->file($legacyPath);
    }

    /**
     * Delete complaint
     */
    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);

        // ✅ Image delete (public/complaints folder)
        if ($complaint->image) {
            if (!Storage::disk('private')->delete($complaint->image)) {
                $legacyPath = public_path($complaint->image);
                if (is_file($legacyPath)) {
                    unlink($legacyPath);
                }
            }
        }

        $complaint->delete();

        return back()->with('success', 'Complaint deleted successfully');
    }
}
