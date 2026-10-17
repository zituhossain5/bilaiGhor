<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductImport\ImportLookups;
use App\Services\ProductImport\ImportResultExport;
use App\Services\ProductImport\ImportStore;
use App\Services\ProductImport\ImportTemplate;
use App\Services\ProductImport\ProductImportPlanner;
use App\Services\ProductImport\ProductImportRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Bulk product import from Excel: template → upload → preview (image links checked) → import
 * in small steps → summary and result file.
 */
class ProductImportController extends Controller
{
    public function __construct(private ProductImportRunner $runner)
    {
        $this->middleware('permission:product-create');
    }

    public function index()
    {
        return view('backEnd.product.import.index', [
            'modes' => ProductImportPlanner::MODES,
            'canUpdate' => Auth::guard('admin')->user()?->can('product-edit'),
        ]);
    }

    public function template()
    {
        $book = (new ImportTemplate())->build(ImportLookups::load());

        return $this->download($book, 'BilaiGhor-Product-Import-Template.xlsx');
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            // .xlsx is a ZIP inside; some servers detect it as plain ZIP.
            'file' => ['required', 'file', 'extensions:xlsx', 'mimes:xlsx,zip', 'max:10240'],
            'images_zip' => ['nullable', 'file', 'extensions:zip', 'mimes:zip', 'max:102400'],
            'mode' => ['required', 'in:' . implode(',', array_keys(ProductImportPlanner::MODES))],
            'auto_create' => ['nullable', 'boolean'],
            'update_stock' => ['nullable', 'boolean'],
        ], [
            'file.mimes' => 'Please upload the Excel template (.xlsx).',
            'file.extensions' => 'Please upload the Excel template (.xlsx).',
            'images_zip.mimes' => 'Images must be uploaded as one .zip file.',
            'images_zip.extensions' => 'Images must be uploaded as one .zip file.',
        ]);
        if ($validated['mode'] !== 'create' && !Auth::guard('admin')->user()?->can('product-edit')) {
            abort(403, 'Updating products needs the product edit permission.');
        }

        ImportStore::prune();
        $id = ImportStore::newId();
        $dir = ImportStore::dir($id);
        File::ensureDirectoryExists($dir);
        $request->file('file')->move($dir, 'upload.xlsx');
        $zipPath = null;
        if ($request->hasFile('images_zip')) {
            $request->file('images_zip')->move($dir, 'images.zip');
            $zipPath = $dir . DIRECTORY_SEPARATOR . 'images.zip';
        }

        try {
            $plan = (new ProductImportPlanner())->analyze($id, $dir . DIRECTORY_SEPARATOR . 'upload.xlsx', $zipPath, [
                'mode' => $validated['mode'],
                'auto_create' => $request->boolean('auto_create'),
                'update_stock' => $validated['mode'] !== 'create' && $request->boolean('update_stock'),
            ], (int) Auth::guard('admin')->id(), $request->file('file')->getClientOriginalName());
        } catch (RuntimeException $e) {
            File::deleteDirectory($dir);

            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        } finally {
            if ($zipPath) {
                File::delete($zipPath); // checked images are staged; the ZIP itself is not kept
            }
        }

        $plan['images_total'] = $this->runner->pendingImageCount($plan);
        if ($plan['images_total'] === 0) {
            $plan['phase'] = 'review';
        }
        ImportStore::save($plan);

        return redirect()->route('products.import.show', $id);
    }

    public function show(string $import)
    {
        $plan = $this->plan($import);

        return view('backEnd.product.import.preview', [
            'plan' => $plan,
            'summary' => $this->runner->summary($plan),
            'modes' => ProductImportPlanner::MODES,
        ]);
    }

    /** One step of checking image links (AJAX). */
    public function images(string $import): JsonResponse
    {
        return $this->step($import, function (array &$plan) {
            if ($plan['phase'] === 'images') {
                $this->runner->checkImages($plan);
            }
        });
    }

    /** One step of the import (AJAX). */
    public function run(string $import): JsonResponse
    {
        return $this->step($import, function (array &$plan) {
            if ($plan['phase'] === 'images') {
                abort(409, 'The image links are still being checked.');
            }
            if ($plan['phase'] !== 'done') {
                $this->runner->importStep($plan, Auth::guard('admin')->id());
            }
        });
    }

    public function result(string $import)
    {
        $plan = $this->plan($import);
        $book = (new ImportResultExport())->build($plan, ImportStore::dir($import) . DIRECTORY_SEPARATOR . 'upload.xlsx');
        $name = pathinfo($plan['file'], PATHINFO_FILENAME) ?: 'products';

        return $this->download($book, 'Result - ' . preg_replace('/[^\w\- ]+/u', '', $name) . '.xlsx');
    }

    // ---------------------------------------------------------------- helpers

    private function step(string $import, callable $work): JsonResponse
    {
        $lock = ImportStore::lock($import);
        if (!$lock->get()) {
            return response()->json(['busy' => true] + $this->progress($this->plan($import)));
        }
        try {
            $plan = $this->plan($import);
            $work($plan);
            ImportStore::save($plan);

            return response()->json($this->progress($plan));
        } finally {
            $lock->release();
        }
    }

    private function progress(array $plan): array
    {
        $summary = $this->runner->summary($plan);

        return [
            'phase' => $plan['phase'],
            'pending_images' => $summary['pending_images'],
            'images_total' => (int) ($plan['images_total'] ?? 0),
            'remaining' => $summary['remaining'],
            'importable' => $summary['importable'],
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'failed' => $summary['failed'],
        ];
    }

    /** The import's plan; only the admin who uploaded it may use it. */
    private function plan(string $import): array
    {
        $plan = ImportStore::load($import);
        abort_unless($plan && (int) $plan['admin_id'] === (int) Auth::guard('admin')->id(), 404);

        return $plan;
    }

    private function download(Spreadsheet $book, string $name)
    {
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $name, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
