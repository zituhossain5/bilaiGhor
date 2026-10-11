<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryThana;
use App\Models\IpBlock;
use App\Models\Payment;
use App\Models\Shipping;
use App\Support\BdPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Toastr;
use Image;
use File;
use Auth;
use Hash;
class CustomerManageController extends Controller
{
    /** Registered and manual customers in one list, searchable by name, mobile (any format) or email. */
    public function index(Request $request){
        $keyword = trim((string) $request->keyword);
        $source = in_array($request->source, [Customer::SOURCE_REGISTERED, Customer::SOURCE_MANUAL], true) ? $request->source : null;

        $show_data = Customer::query()
            ->with(['district:id,name', 'thana:id,name,name_bn'])
            ->when($keyword !== '', fn ($q) => $this->applySearch($q, $keyword))
            ->when($source, fn ($q) => $q->where('source', $source))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('backEnd.customer.index', compact('show_data', 'keyword', 'source'));
    }

    /** Add a manual customer — every field is optional. */
    public function create(){
        return view('backEnd.customer.edit', [
            'edit_data' => null,
            'districts' => DeliveryDistrict::active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request){
        $validated = $this->validateCustomer($request, null);

        $customer = new Customer($this->details($validated));
        $customer->source = Customer::SOURCE_MANUAL;
        $customer->status = $request->boolean('status', true) ? 'active' : 'inactive';
        $customer->slug = $customer->name ? Str::slug($customer->name) . '-' . Str::lower(Str::random(6)) : null;
        $customer->save();

        $this->flashDuplicates($customer);
        Toastr::success('Customer added', 'Success');
        return redirect()->route('customers.index');
    }

    public function edit($id){
        return view('backEnd.customer.edit', [
            'edit_data' => Customer::findOrFail($id),
            'districts' => DeliveryDistrict::active()->ordered()->get(['id', 'name']),
        ]);
    }

    public function update(Request $request){
        $update_data = Customer::findOrFail($request->hidden_id);
        $validated = $this->validateCustomer($request, $update_data);

        $input = $this->details($validated);
        $input['status'] = $request->boolean('status', true) ? 'active' : 'inactive';

        // Password and photo belong to registered accounts; manual customers never log in.
        if (! $update_data->isManual()) {
            if (! empty($validated['password'])) {
                $input['password'] = Hash::make($validated['password']);
            }

            $image = $request->file('image');
            if ($image) {
                $imageUrl = 'public/uploads/customer/' . time() . '-' . Str::lower(Str::random(8)) . '.webp';
                File::ensureDirectoryExists(dirname($imageUrl));
                $img = Image::make($image->getRealPath());
                $img->encode('webp', 90);
                $width = 100;
                $height = 100;
                $img->height() > $img->width() ? $width = null : $height = null;
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save($imageUrl);
                if ($update_data->image && ! str_contains($update_data->image, '/default/')) {
                    File::delete($update_data->image);
                }
                $input['image'] = $imageUrl;
            }
        }

        $update_data->update($input);

        $this->flashDuplicates($update_data);
        Toastr::success('Success','Data update successfully');
        return redirect()->route('customers.index');
    }

    /**
     * Customer lookup for admin forms (Create Manual Invoice). ?q= searches name, mobile and email;
     * ?phone= matches the mobile only. At most 20 results, only the fields the form fills.
     */
    public function search(Request $request): JsonResponse
    {
        $phoneMode = $request->has('phone');
        $term = trim((string) ($phoneMode ? $request->query('phone') : $request->query('q')));
        $digits = BdPhone::normalize($term);

        if ($phoneMode ? strlen((string) $digits) < 4 : mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $query = Customer::query()
            ->with(['addresses' => fn ($q) => $q->orderByDesc('is_default')->orderByDesc('id')])
            ->limit(20);

        if ($phoneMode) {
            $query->where('phone_normalized', 'like', '%' . $digits . '%');
        } else {
            $this->applySearch($query, $term);
        }

        $customers = $query
            ->orderByRaw('CASE WHEN phone_normalized = ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [
                (string) $digits,
                addcslashes($term, '%_\\') . '%',
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return response()->json(['results' => $this->searchResults($customers, (string) $digits)]);
    }
 
    public function inactive(Request $request){
        $inactive = Customer::find($request->hidden_id);
        $inactive->status = 'inactive';
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request){
        $active = Customer::find($request->hidden_id);
        $active->status = 'active';
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function profile(Request $request){
        $profile = Customer::with('orders')->find($request->id);
        return view('backEnd.customer.profile',compact('profile'));
    }
    public function adminlog(Request $request){
        $customer = Customer::findOrFail($request->hidden_id);
        if ($customer->isManual()) {
            Toastr::error('Manual customers have no account to log in to.', 'Not available');
            return redirect()->back();
        }
        Auth::guard('customer')->loginUsingId($customer->id);
        return redirect()->route('customer.account');
    }
    public function ip_block(Request $request){
        $data = IpBlock::get();
        // Query parameter থেকে IP এবং reason নেওয়া
        $prefillIp = $request->query('ip');
        $prefillReason = $request->query('reason', 'ফেইক অর্ডার');
        return view('backEnd.reports.ipblock',compact('data', 'prefillIp', 'prefillReason'));
    }
    public function ipblock_store(Request $request){

        $store_data = new IpBlock();
        $store_data->ip_no = $request->ip_no;
        $store_data->reason = $request->reason;
        $store_data->save();
        Toastr::success('Success','IP address add successfully');
        return redirect()->back();
    }
    public function ipblock_update(Request $request){
        $update_data = IpBlock::find($request->id);
        if (!$update_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        $update_data->ip_no = $request->ip_no;
        $update_data->reason = $request->reason;
        $update_data->save();
        Toastr::success('Success','IP address update successfully');
        return redirect()->back();
    }
    public function ipblock_destroy(Request $request){
        $delete_data = IpBlock::find($request->id);
        if (!$delete_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        $delete_data->delete();
        Toastr::success('Success','IP address delete successfully');
        return redirect()->back();
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $this->removeCustomer($customer);

        Toastr::success('Success', 'Customer deleted successfully');
        return redirect()->route('customers.index');
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'confirm' => 'required|in:DELETE',
        ]);

        $deleted = 0;

        DB::transaction(function () use (&$deleted) {
            Customer::query()->orderBy('id')->chunkById(100, function ($customers) use (&$deleted) {
                foreach ($customers as $customer) {
                    $this->removeCustomer($customer);
                    $deleted++;
                }
            });
        });

        Toastr::success('Success', "Deleted {$deleted} customer(s) successfully");
        return redirect()->route('customers.index');
    }

    protected function removeCustomer(Customer $customer): void
    {
        if ($customer->image && File::exists($customer->image)) {
            File::delete($customer->image);
        }

        Payment::where('customer_id', $customer->id)->delete();
        Shipping::where('customer_id', $customer->id)->delete();

        $customer->tokens()->delete();
        $customer->roles()->detach();
        $customer->permissions()->detach();

        $customer->delete();
    }
    
    /**
     * Format-only checks. A manual customer needs nothing filled in; a registered customer keeps
     * a name and phone (the phone is their login), unique among registered accounts.
     */
    private function validateCustomer(Request $request, ?Customer $customer): array
    {
        $registered = $customer && ! $customer->isManual();
        $uniqueAmongRegistered = fn (string $column) => Rule::unique('customers', $column)
            ->where('source', Customer::SOURCE_REGISTERED)
            ->ignore($customer?->id);

        $rules = [
            'name'        => [$registered ? 'required' : 'nullable', 'string', 'max:155'],
            'phone'       => [$registered ? 'required' : 'nullable', 'string', 'max:55', 'regex:/^\+?[0-9][0-9 \-]*$/'],
            'email'       => ['nullable', 'email', 'max:55'],
            'address'     => ['nullable', 'string', 'max:255'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'thana_id'    => ['nullable', 'integer', 'exists:thanas,id'],
            'post_code'   => ['nullable', 'string', 'max:20'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];

        if ($registered) {
            $rules['phone'][] = $uniqueAmongRegistered('phone');
            $rules['email'][] = $uniqueAmongRegistered('email');
            $rules['password'] = ['nullable', 'string', 'min:6', 'max:100'];
            $rules['image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'Mobile number may contain only digits (and a leading +).',
        ]);

        // A thana pins its district; a mismatched pair is refused rather than half-saved.
        if (! empty($validated['thana_id'])) {
            $thanaDistrict = (int) DeliveryThana::whereKey($validated['thana_id'])->value('district_id');
            if (empty($validated['district_id'])) {
                $validated['district_id'] = $thanaDistrict;
            } elseif ((int) $validated['district_id'] !== $thanaDistrict) {
                throw ValidationException::withMessages(['thana_id' => 'The selected thana is not in the selected district.']);
            }
        }

        return $validated;
    }

    /** The saved details from a validated form, blanks stored as null. */
    private function details(array $validated): array
    {
        $details = [];
        foreach (['name', 'phone', 'email', 'address', 'district_id', 'thana_id', 'post_code', 'notes'] as $field) {
            $value = $validated[$field] ?? null;
            $details[$field] = is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value;
        }

        return $details;
    }

    /** Warn (never block) when another customer already has this mobile number. */
    private function flashDuplicates(Customer $customer): void
    {
        $others = $this->samePhone($customer->phone, $customer->id);
        if ($others->isNotEmpty()) {
            session()->flash('customer_duplicates', [
                'customer' => $customer->name ?: 'This customer',
                'others'   => $others->map(fn ($c) => ['id' => $c->id, 'name' => $c->name ?: 'Unnamed', 'manual' => $c->isManual()])->all(),
            ]);
        }
    }

    private function samePhone(?string $phone, ?int $exceptId)
    {
        if (! BdPhone::normalize($phone)) {
            return collect();
        }

        return Customer::withPhone($phone)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name', 'source']);
    }

    /** Name or email contains the text, or the mobile contains its digits (any format). */
    private function applySearch($query, string $term): void
    {
        $digits = BdPhone::normalize($term);
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function ($w) use ($like, $digits) {
            $w->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);
            if ($digits !== null && strlen($digits) >= 3) {
                $w->orWhere('phone_normalized', 'like', '%' . $digits . '%');
            }
        });
    }

    /**
     * Rows for the invoice customer picker: "Name — Mobile — District/Thana". Blank address fields
     * fall back to the customer's default saved address.
     */
    private function searchResults($customers, string $digits): array
    {
        $rows = $customers->map(function (Customer $c) {
            $saved = $c->addresses->first();

            return [
                'id'          => $c->id,
                'name'        => $c->name ?: ($saved->name ?? null),
                'phone'       => $c->phone ?: ($saved->phone ?? null),
                'email'       => $c->email ?: ($saved->email ?? null),
                'address'     => $c->address ?: ($saved->address ?? null),
                'district_id' => $c->district_id ?: ($saved->district_id ?? null),
                'thana_id'    => $c->thana_id ?: ($saved->thana_id ?? null),
                'post_code'   => $c->post_code ?: ($saved->post_code ?? null),
                'manual'      => $c->isManual(),
                'phone_normalized' => $c->phone_normalized,
            ];
        });

        $districts = DeliveryDistrict::whereIn('id', $rows->pluck('district_id')->filter())->pluck('name', 'id');
        $thanas = DeliveryThana::whereIn('id', $rows->pluck('thana_id')->filter())->get(['id', 'name', 'name_bn'])->keyBy('id');

        return $rows->map(function (array $row) use ($districts, $thanas, $digits) {
            $thana = $thanas[$row['thana_id']] ?? null;
            $row['location'] = collect([$districts[$row['district_id']] ?? null, $thana ? ($thana->name_bn ?: $thana->name) : null])
                ->filter()->implode(' / ');
            $row['exact'] = $digits !== '' && $row['phone_normalized'] === $digits && BdPhone::isComplete($digits);
            unset($row['phone_normalized']);

            return $row;
        })->values()->all();
    }

    // AJAX method for quick IP block from order page
    public function ipblock_quick_store(Request $request){
        try {
            $ip = $request->ip;
            $reason = $request->reason ?? 'ফেইক অর্ডার';
            
            if(!$ip){
                return response()->json([
                    'status' => 'error',
                    'message' => 'IP address is required'
                ], 400);
            }
            
            // Check if IP already blocked
            $existing = IpBlock::where('ip_no', $ip)->first();
            if($existing){
                return response()->json([
                    'status' => 'error',
                    'message' => 'This IP is already blocked'
                ], 400);
            }
            
            $store_data = new IpBlock();
            $store_data->ip_no = $ip;
            $store_data->reason = $reason;
            $store_data->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'IP address blocked successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to block IP: ' . $e->getMessage()
            ], 500);
        }
    }
}
