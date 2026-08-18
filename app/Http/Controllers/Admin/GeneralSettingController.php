<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Toastr;
use Image;
use File;
use DB;
class GeneralSettingController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:setting-list|setting-create|setting-edit|setting-delete', ['only' => ['index','store']]);
        $this->middleware('permission:setting-create', ['only' => ['create','store']]);
        $this->middleware('permission:setting-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:setting-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        // ✅ Single record pattern: redirect directly to edit/create without listing/loop
        $setting = GeneralSetting::orderBy('id', 'desc')->first();

        if ($setting) {
            return redirect()->route('settings.edit', $setting->id);
        }

        return redirect()->route('settings.create');
    }
    public function create()
    {
        return view('backEnd.settings.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'fraud_api_key' => 'required',
            'copyright_color' => 'required',
            'primary_color' => 'required',
            'secodery_color' => 'required',
            'footer_color' => 'required',
            'facebook_page_username' => 'required',
            'white_logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'dark_logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'og_baner' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'favicon' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'required',
        ]);

        $input = $request->except(['_token', 'white_logo', 'dark_logo', 'favicon', 'og_baner']);
        $input['white_logo'] = $this->storeSettingImage($request->file('white_logo'), 'white_logo');
        $input['dark_logo'] = $this->storeSettingImage($request->file('dark_logo'), 'dark_logo');
        $input['og_baner'] = $this->storeSettingImage($request->file('og_baner'), 'og_baner');
        $input['favicon'] = $this->storeSettingImage($request->file('favicon'), 'favicon', 'png', 32, 32);
        
        $input['vendor_enabled'] = $request->has('vendor_enabled') ? 1 : 0;
        $input['reseller_enabled'] = $request->has('reseller_enabled') ? 1 : 0;
        $input['checkout_otp_enabled'] = $request->has('checkout_otp_enabled') ? 1 : 0;

        GeneralSetting::create($input);

        // APP_NAME sync
        if (!empty($input['name'])) {
            $this->updateEnvAppName($input['name']);
        }

        Toastr::success('Success','Data insert successfully');
        return redirect()->route('settings.index');
    }
    
    public function edit($id)
    {
        $edit_data = GeneralSetting::find($id);
        return view('backEnd.settings.edit',compact('edit_data'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'white_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'dark_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'og_baner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'favicon' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $update_data = GeneralSetting::findOrFail($request->id);
        $input = $request->except(['_token', 'white_logo', 'dark_logo', 'favicon', 'og_baner']);

        foreach (['white_logo', 'dark_logo', 'og_baner'] as $field) {
            $input[$field] = $request->hasFile($field)
                ? $this->storeSettingImage($request->file($field), $field)
                : $update_data->{$field};
        }

        $input['favicon'] = $request->hasFile('favicon')
            ? $this->storeSettingImage($request->file('favicon'), 'favicon', 'webp', 32, 32)
            : $update_data->favicon;
        $input['status'] = 1;
        
        // Handle vendor_enabled and reseller_enabled (checkbox returns '1' if checked, null if unchecked)
        $input['vendor_enabled'] = $request->has('vendor_enabled') ? 1 : 0;
        $input['reseller_enabled'] = $request->has('reseller_enabled') ? 1 : 0;
        $input['checkout_otp_enabled'] = $request->has('checkout_otp_enabled') ? 1 : 0;

        $update_data->update($input);

        // APP_NAME sync: site title পরিবর্তন হলে .env আপডেট করো
        if (!empty($input['name'])) {
            $this->updateEnvAppName($input['name']);
        }

        Cache::forget('general_setting');
        Cache::forget('frontend_homepage_v1');
        Cache::forget('side_categories');
        Cache::forget('menu_categories');
        Cache::forget('brands_list');
        Cache::forget('pages_top');
        Cache::forget('pages_right');
        Cache::forget('common_menu');

        Toastr::success('Settings updated successfully!', 'Success');
        return redirect()->route('settings.edit', ['id' => $update_data->id]);
    }
 
    public function inactive(Request $request)
    {
        $inactive = GeneralSetting::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = GeneralSetting::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = GeneralSetting::find($request->hidden_id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }

    private function storeSettingImage(
        UploadedFile $file,
        string $field,
        string $format = 'webp',
        ?int $width = null,
        ?int $height = null
    ): string {
        $directory = public_path('uploads/settings');

        try {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true, true);
            }
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $field => 'The settings upload directory could not be created.',
            ]);
        }

        if (!is_writable($directory)) {
            throw ValidationException::withMessages([
                $field => 'The settings upload directory is not writable.',
            ]);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'settings-image';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'-'.$safeName.'.'.$format;
        $absolutePath = $directory.DIRECTORY_SEPARATOR.$filename;

        try {
            $image = Image::make($file->getRealPath())->encode($format, 90);

            if ($width !== null || $height !== null) {
                $image->resize($width, $height);
            }

            $image->save($absolutePath);
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $field => 'The image could not be saved. Please try another valid image.',
            ]);
        }

        return 'public/uploads/settings/'.$filename;
    }

    private function updateEnvAppName(string $name): void
    {
        $envPath = base_path('.env');
        if (!is_file($envPath) || !is_writable($envPath)) {
            return;
        }

        $content    = file_get_contents($envPath);
        $escapedName = str_contains($name, ' ') ? '"' . addslashes($name) . '"' : $name;

        if (preg_match('/^APP_NAME=.*/m', $content)) {
            $content = preg_replace('/^APP_NAME=.*/m', 'APP_NAME=' . $escapedName, $content);
        } else {
            $content .= "\nAPP_NAME=" . $escapedName;
        }

        file_put_contents($envPath, $content);

        // OPcache & config cache clear
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        try {
            \Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // silent
        }
    }
}
