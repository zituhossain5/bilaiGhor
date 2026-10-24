@extends('backEnd.layouts.master')
@section('title', 'Steadfast Settings')

@section('css')
<style>
    .sfs-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; margin-bottom:20px; }
    .sfs-card-head { padding:14px 18px; border-bottom:1px solid #f1f5f9; }
    .sfs-card-head h5 { margin:0; font-size:15px; font-weight:700; }
    .sfs-card-body { padding:16px 18px; }
    .sfs-switch { display:flex; gap:10px; align-items:flex-start; padding:10px 0; border-bottom:1px dashed #eef2f7; }
    .sfs-switch:last-child { border-bottom:0; }
    .sfs-switch input[type=checkbox] { width:18px; height:18px; margin-top:2px; flex-shrink:0; }
    .sfs-switch label { font-weight:600; margin:0; cursor:pointer; }
    .sfs-help { font-size:12.5px; color:#64748b; margin-top:2px; }
    .sfs-note { background:#fffbeb; border:1px solid #fde68a; color:#78350f; border-radius:8px; padding:10px 12px; font-size:13px; }
    @media (max-width:575.98px) { .sfs-card-body { padding:12px; } }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row"><div class="col-12"><div class="page-title-box"><h4 class="page-title">Steadfast Settings</h4></div></div></div>

    @unless($ready)
        <div class="alert alert-warning">সেটিংস টেবিল তৈরি হয়নি — সার্ভারে <code>php artisan migrate</code> চালান।</div>
    @endunless

    <div class="sfs-card">
        <div class="sfs-card-head"><h5><i class="fas fa-sliders-h me-1"></i> পার্সেল পাঠানোর সেটিংস</h5></div>
        <div class="sfs-card-body">
            <div class="sfs-note mb-3">
                Steadfast API-তে ওজনের আলাদা ফিল্ড নেই। ওজন অর্ডারে সংরক্ষিত হয় এবং চালু থাকলে নোটের শেষে "Weight: 1.5 kg" হিসেবে পাঠানো হয়।
            </div>
            <form method="POST" action="{{ route('admin.steadfast.settings.update') }}">
                @csrf
                <div class="sfs-switch">
                    <input type="checkbox" name="weight_required" id="s_wreq" value="1" @checked($settings['weight_required'] === '1') @disabled(!$ready)>
                    <div><label for="s_wreq">ওজন ছাড়া Steadfast এ পাঠানো যাবে না</label>
                        <div class="sfs-help">পাঠানোর ডায়ালগে প্রতিটি অর্ডারের ওজন (kg) দিতে হবে। ওজন নিজে থেকে বসানো হয় না।</div></div>
                </div>
                <div class="sfs-switch">
                    <input type="checkbox" name="weight_in_note" id="s_wnote" value="1" @checked($settings['weight_in_note'] === '1') @disabled(!$ready)>
                    <div><label for="s_wnote">নোটের শেষে ওজন লিখুন ("Weight: 1.5 kg")</label>
                        <div class="sfs-help">অর্ডারের নোট থাকবে; মোট ২৫০ অক্ষরের মধ্যে রাখা হয়।</div></div>
                </div>
                <div class="sfs-switch">
                    <div style="width:18px;"></div>
                    <div><label for="s_wmax">সর্বোচ্চ ওজন (kg)</label>
                        <input type="number" name="weight_max_kg" id="s_wmax" class="form-control form-control-sm mt-1" style="max-width:140px;" min="1" max="999" step="0.01"
                               value="{{ old('weight_max_kg', $settings['weight_max_kg']) }}" @disabled(!$ready)>
                        @error('weight_max_kg')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="sfs-switch">
                    <div style="width:18px;"></div>
                    <div><label>Steadfast এ "total_lot" হিসেবে কী যাবে</label>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="steadfast_total_lot" id="s_lot_items" value="items" @checked($settings['steadfast_total_lot'] !== 'packages') @disabled(!$ready)>
                            <label class="form-check-label fw-normal" for="s_lot_items">মোট আইটেম সংখ্যা</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="steadfast_total_lot" id="s_lot_pk" value="packages" @checked($settings['steadfast_total_lot'] === 'packages') @disabled(!$ready)>
                            <label class="form-check-label fw-normal" for="s_lot_pk">প্যাকেজ সংখ্যা (পাঠানোর সময় বদলানো যায়)</label>
                        </div>
                        @error('steadfast_total_lot')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="sfs-switch">
                    <input type="checkbox" name="steadfast_pickup_reminder" id="s_pick" value="1" @checked($settings['steadfast_pickup_reminder'] === '1') @disabled(!$ready)>
                    <div><label for="s_pick">ড্যাশবোর্ডে পিকআপ রিমাইন্ডার দেখান</label>
                        <div class="sfs-help">পার্সেল পাঠালে Steadfast পোর্টালে নিজে পিকআপ রিকোয়েস্ট দেওয়ার কথা মনে করাবে।</div></div>
                </div>
                <button type="submit" class="btn btn-primary mt-2" @disabled(!$ready)><i class="fas fa-save me-1"></i> সেভ করুন</button>
            </form>
        </div>
    </div>
</div>
@endsection
