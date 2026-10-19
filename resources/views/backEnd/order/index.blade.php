@extends('backEnd.layouts.master')
@section('title', $order_status->name . ' অর্ডার')

@section('css')
@include('backEnd.order.partials.index_styles')
@endsection

@section('content')
@include('backEnd.order.partials.index_header')
<tbody>
                                @foreach($show_data as $key => $value)
                                    <tr>
                                        @php
                                            $paymentState = \App\Services\OrderPaymentService::state($value);
                                            $rowParcel = $value->courierParcel;
                                            $rowCourierId = $value->courier_tracking_id ?: ($rowParcel->consignment_id ?? '');
                                            $rowCourier = $value->courier_tracking_id ? ($value->courier_type ?: 'steadfast') : ($rowParcel->courier ?? '');
                                        @endphp
                                        {{-- Every order can be selected (manual orders too). Deleting a manual order is still refused
                                             by the server (PreventManualOrderDeletion) and by the bulk-delete button. --}}
                                        <td class="oi-select-cell">
                                            <input type="checkbox" class="checkbox form-check-input" value="{{ $value->id }}" autocomplete="off"
                                                aria-label="অর্ডার #{{ $value->invoice_id }} সিলেক্ট"
                                                data-invoice="{{ $value->invoice_id }}"
                                                data-name="{{ $value->shipping->name ?? '' }}"
                                                data-phone="{{ $value->shipping->phone ?? '' }}"
                                                data-cod="{{ max(0, (float) $value->amount - (float) $paymentState['paid']) }}"
                                                data-status="{{ (int) $value->order_status }}"
                                                data-courier-id="{{ $rowCourierId }}"
                                                data-courier="{{ $rowCourier ? \App\Services\CourierDispatchService::label($rowCourier) : '' }}"
                                                data-manual="{{ $value->is_manual_order ? 1 : 0 }}">
                                        </td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="oi-actions-cell">
                                            <div class="oi-row-actions">
                                                <button type="button"
                                                    class="oi-act-btn oi-act-view order-quick-view-btn"
                                                    data-order-id="{{ $value->id }}"
                                                    title="বিস্তারিত দেখুন"
                                                    aria-label="বিস্তারিত দেখুন">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                    <span class="oi-act-label">ভিউ</span>
                                                </button>
                                                @if($value->is_manual_order)
                                                    <a href="{{ route('admin.manual_orders.edit', $value) }}" class="oi-act-btn" title="Edit manual order">
                                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                                        <span class="oi-act-label">Edit</span>
                                                    </a>
                                                @else
                                                <form method="post" action="{{ route('admin.order.destroy') }}" class="oi-act-delete-form">
                                                    @csrf
                                                    <input type="hidden" value="{{ $value->id }}" name="id">
                                                    <button type="submit"
                                                        title="ডিলিট"
                                                        aria-label="অর্ডার ডিলিট"
                                                        class="oi-act-btn oi-act-delete delete-confirm">
                                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                        <span class="oi-act-label">ডিলিট</span>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                        <td><a href="{{ route('admin.order.process', ['invoice_id' => $value->invoice_id]) }}" class="oi-invoice-link">#{{ $value->invoice_id }}</a></td>
                                        <td>
                                            {{ date('d-m-Y', strtotime($value->updated_at)) }}<br>
                                            {{ date('h:i:s a', strtotime($value->updated_at)) }}
                                        </td>
                                        <td>
                                            <strong>{{ $value->shipping ? $value->shipping->name : '' }}</strong>
                                            <p class="mb-0">{{ $value->shipping ? $value->shipping->phone : '' }}</p>
                                        </td>
                                        <td>
                                            @php
                                                $tsKey = $value->is_manual_order
                                                    ? strtolower(trim((string) ($value->order_source ?: 'manual')))
                                                    : strtolower(trim((string) ($value->traffic_source ?? 'direct')));
                                                $trafficOpts = isset($traffic_source_options) ? $traffic_source_options : [];
                                                $tsLabel = $value->is_manual_order
                                                    ? ucwords(str_replace(['_', '-'], ' ', $tsKey ?: 'manual'))
                                                    : (isset($trafficOpts[$tsKey]) ? $trafficOpts[$tsKey] : ucfirst($tsKey ?: 'direct'));
                                                $tsBadgeClass = match ($tsKey) {
                                                    'facebook' => 'bg-primary',
                                                    'instagram' => 'bg-danger',
                                                    'google' => 'bg-success',
                                                    'tiktok' => 'bg-dark',
                                                    'youtube' => 'bg-danger',
                                                    'whatsapp' => 'bg-success',
                                                    'bing' => 'bg-info',
                                                    'yahoo' => 'bg-secondary',
                                                    'twitter' => 'bg-info',
                                                    'direct' => 'bg-secondary',
                                                    'other' => 'bg-warning',
                                                    default => 'bg-secondary',
                                                };
                                                $tsTitleTip = $value->is_manual_order
                                                    ? 'Manual/offline order source'
                                                    : (isset($value->traffic_referrer) ? trim((string) $value->traffic_referrer) : '');
                                            @endphp
                                            <span class="badge {{ $tsBadgeClass }}" @if($tsTitleTip !== '') title="{{ Str::limit($tsTitleTip, 240) }}" @endif>{{ Str::limit($tsLabel, 16) }}</span>
                                            @if($tsTitleTip !== '')
                                                <br><small class="text-muted" style="font-size: .68rem;">{{ Str::limit($tsTitleTip, 42) }}</small>
                                            @endif
                                        </td>

                                        {{-- Amount (show remaining if partial paid) --}}
                                        <td>
                                            @php
                                                $paid = (float) $paymentState['paid'];
                                                $total = floatval($value->amount);
                                                $showAmount = $total;
                                                if ($paid > 0 && $paid < $total) {
                                                    $showAmount = $total - $paid;
                                                }
                                            @endphp
                                            <span class="oi-amount">৳{{ number_format($showAmount, 2) }}</span>
                                        </td>

                                        <td>
                                            @include('backEnd.order.partials.inline_status_select', [
                                                'order' => $value,
                                                'type' => 'order',
                                                'statuses' => $orderstatus,
                                            ])
                                            @include('backEnd.order.partials.courier_badge', ['order' => $value])
                                        </td>

                                        <td>
                                            @include('backEnd.order.partials.inline_status_select', [
                                                'order' => $value,
                                                'type' => 'payment',
                                                'paymentState' => $paymentState,
                                            ])
                                        </td>

                                        <td>
                                            {{-- 
                                                LOGIC:
                                                - is_null() ব্যবহার করা হয়েছে কারণ 0 একটি ভ্যালিড রেট হতে পারে (ফ্রড)।
                                                - NULL হলে "যাচাই করুন" (হলুদ)।
                                                - অন্যথায় রেট দেখাবে (সবুজ/লাল)।
                                            --}}
                                            @if(is_null($value->fraud_rate))
                                                 <a href="javascript:void(0);" 
                                                class="btn btn-sm fraud-check"
                                                data-mobile="{{ $value->shipping ? $value->shipping->phone : '' }}"
                                                style="background:#fb8709; color:#fff; padding:5px 12px; border-radius:6px; font-size:13px;">
                                                চেকিং
                                            </a>
                                            @else
                                                <a href="javascript:void(0);" 
                                                   class="btn btn-sm fraud-check {{ $value->fraud_rate >= 80 ? 'btn-success' : 'btn-danger' }}"
                                                   data-mobile="{{ $value->shipping ? $value->shipping->phone : '' }}"
                                                   data-id="{{ $value->id }}"
                                                   style="padding:5px 12px; border-radius:6px; font-size:13px;">
                                                    {{ $value->fraud_rate }}% {{ $value->fraud_rate >= 80 ? 'নিরাপদ' : 'ঝুঁকি' }}
                                                </a>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="oi-paginate custom-paginate order-custom-paginate mt-3">
                        {{ $show_data->links('pagination::bootstrap-4') }}
                    </div>
        </div>
    </div>
</div>

@include('backEnd.order.partials.order_quick_view_modal')

<div class="modal fade oi-modal" id="asignUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-1"></i> ইউজার অ্যাসাইন</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.order.assign') }}" id="order_assign">
        <div class="modal-body">
            <div class="form-group">
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">Select..</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade oi-modal" id="changeStatus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-flag me-1"></i> স্ট্যাটাস পরিবর্তন</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.order.status') }}" id="order_status_form" novalidate>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Select Status <span class="text-danger">*</span></label>
                <select name="order_status" id="order_status" class="form-control">
                    <option value="">Select Status..</option>
                    @if(isset($orderstatus) && $orderstatus->count() > 0)
                        @foreach($orderstatus as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    @else
                        <option value="">No status available</option>
                    @endif
                </select>
                <small class="text-muted">Select orders first, then choose status</small>
                <div class="invalid-feedback" id="status_error" style="display: none;">Please select a status</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade oi-modal" id="pathao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-truck me-1"></i> Pathao কুরিয়ার</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.order.pathao') }}" id="order_sendto_pathao" method="POST">
      @csrf
      <input type="hidden" name="order_ids" id="pathao_order_ids" value="">
      <div class="modal-body">
        <div class="form-group">
            <label for="pathaostore" class="form-label">Store</label>
           <select name="pathaostore" id="pathaostore" class="pathaostore form-control" >
             <option value="">Select Store...</option>
             @if(isset($pathaostore['data']['data']))
                 @foreach($pathaostore['data']['data'] as $store)
                     <option value="{{ $store['store_id'] }}">{{ $store['store_name'] }}</option>
                 @endforeach
             @endif
           </select>
        </div>

        <div class="form-group mt-3">
          <label for="pathaocity" class="form-label">City</label>
           <select name="pathaocity" id="pathaocity" class="chosen-select pathaocity form-control" style="width:100%" >
             <option value="">Select City...</option>
             @if(isset($pathaocities['data']['data']))
                 @foreach($pathaocities['data']['data'] as $city)
                     <option value="{{ $city['city_id'] }}">{{ $city['city_name'] }}</option>
                 @endforeach
             @endif
           </select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">Zone</label>
             <select name="pathaozone" id="pathaozone" class="pathaozone chosen-select form-control" style="width:100%"></select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">Area</label>
             <select name="pathaoarea" id="pathaoarea" class="pathaoarea chosen-select form-control" style="width:100%"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success">Submit</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade oi-modal" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-white" id="noteModalLabel">Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="note_order_id">
        <input type="hidden" id="note_type">

        <div class="form-group">
            <label id="note_label">Note</label>
            <textarea id="note_modal_text" class="form-control" rows="5" placeholder="Write note here..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="saveNoteBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fraudCheckModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header" style="background:#10b981; color:#fff;">
                <h5 class="modal-title">
                    <i class="fe-shield"></i> ফ্রড চেকার রিপোর্ট
                </h5>
                <button type="button" class="btn-close btn-light" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="fraudModalBody" style="min-height:250px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                    <p class="mt-3 fw-bold">ডাটা লোড হচ্ছে...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    // Safe number helper
    function toNum(v) {
        if (v === null || v === undefined || v === '') return 0;
        var n = Number(v);
        return isNaN(n) ? 0 : n;
    }

    // buildSummary: Updated to handle New API keys
    function buildSummary(raw) {
        var pathao = raw.pathao || raw.Pathao || raw.pathao_data || raw.pathao || {};
        var redx = raw.redx || raw.RedX || raw.redx_data || raw.redx || {};
        var steadfast = raw.steadfast || raw.Steadfast || raw.steadfast_data || raw.steadfast || {};
        var parceldex = raw.parceldex || raw.ParcelDex || {};
        var paperfly = raw.paperfly || raw.PaperFly || {};

        function getStats(obj) {
            var t = toNum(obj.total_parcel || obj.total || obj.orders || obj.count);
            var s = toNum(obj.success_parcel || obj.success || obj.complete || obj.delivered);
            var c = toNum(obj.cancelled_parcel || obj.cancel || obj.cancelled || obj.failed);
            var r = (obj.success_ratio !== undefined) ? toNum(obj.success_ratio) : (t > 0 ? Math.round((s / t) * 100) : 0);
            return { total: t, success: s, cancel: c, rate: r };
        }

        var p = getStats(pathao);
        var r = getStats(redx);
        var s = getStats(steadfast);
        var pd = getStats(parceldex);
        var pf = getStats(paperfly);

        var total = p.total + r.total + s.total + pd.total + pf.total;
        var success = p.success + r.success + s.success + pd.success + pf.success;
        var cancel = p.cancel + r.cancel + s.cancel + pd.cancel + pf.cancel;

        var rate = 0;
        if (total > 0) rate = Math.round((success / total) * 100);

        return {
            total: total,
            success: success,
            cancel: cancel,
            rate: rate,
            couriers: {
                Pathao: p,
                RedX: r,
                Steadfast: s,
                ParcelDex: pd,
                PaperFly: pf
            }
        };
    }

    // Render HTML for modal from canonical summary (IN BANGLA)
    function loadFraudHtml(data, mobile) {
        if (data.total === 0) {
            return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#f8f9fa;border-radius:8px;">
                    <h5><i class="fe-phone-call"></i> ${mobile}</h5>
                    <small>সফলতার হার: 0%</small>
                    <span class="badge bg-secondary float-end">কোন তথ্য নেই</span>
                </div>
                <div class="alert alert-light text-center py-3" style="border:1px solid #ddd;">
                    <h5 class="text-muted mb-0">😕 কোনো তথ্য খুঁজে পাওয়া যায়নি</h5>
                    <small>এই কাস্টমারের সম্পর্কে কোনো তথ্য পাওয়া যায়নি। অতিরিক্ত সতর্কতার জন্য নিজের যাচাই করুন।</small>
                </div>
            </div>`;
        }

        var rateText = (data.rate || data.rate === 0) ? (data.rate + '%') : 'N/A';
        
        // Bangla Risk Tags
        var riskTag = '<span class="badge bg-success">নিরাপদ</span>';
        var showWarning = (data.total > 0 && data.rate < 80);
        if (showWarning) { riskTag = '<span class="badge bg-danger">উচ্চ ঝুঁকি</span>'; }

        var courierRows = '';
        Object.entries(data.couriers).forEach(function([name, c]) {
            if(c.total === 0) return;

            var cRateNum = toNum(c.rate);
            var cRate = (c.total === 0) ? 'N/A' : (cRateNum + '%');
            var badgeClass = 'bg-secondary';
            if (c.total === 0) { badgeClass = 'bg-secondary'; }
            else if (cRateNum >= 90) { badgeClass = 'bg-success'; }
            else if (cRateNum >= 70) { badgeClass = 'bg-warning text-dark'; }
            else { badgeClass = 'bg-danger'; }

            courierRows += `
                <tr>
                    <td>${name}</td>
                    <td>${c.total}</td>
                    <td class="text-success">${c.success}</td>
                    <td class="text-danger">${c.cancel}</td>
                    <td><span class="badge ${badgeClass}">${cRate}</span></td>
                </tr>`;
        });

        var warningHtml = '';
        if (showWarning) {
            warningHtml = `<div class="alert alert-danger text-center py-2">⚠️ সতর্কতা: ডেলিভারি হার কম - COD যাচাই করুন অথবা এডভান্স নিন</div>`;
        } else {
            warningHtml = `<div class="text-start mb-3"><small class="text-success">✓ নিরাপদ - কাস্টমারের ডেলিভারি রেকর্ড ভালো।</small></div>`;
        }

        return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#e8fff3;border-radius:8px;">
                    <h5><i class="fe-phone-call"></i> ${mobile}</h5>
                    <small>সফলতার হার: ${rateText}</small>
                    <span class="float-end">${riskTag}</span>
                </div>
                ${warningHtml}
                <div class="row text-center mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#6366f1;border-radius:10px;">
                            <h3>${data.total}</h3><span>মোট পার্সেল</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#10b981;border-radius:10px;">
                            <h3>${data.success}</h3><span>ডেলিভারি</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#ef4444;border-radius:10px;">
                            <h3>${data.cancel}</h3><span>বাতিল/রিটার্ন</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#f97316;border-radius:10px;">
                            <h3>${rateText}</h3><span>হার</span>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>কুরিয়ার</th><th>মোট</th><th>সফল</th><th>বাতিল</th><th>হার</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${courierRows}
                    </tbody>
                </table>
            </div>
        `;
    }
</script>

<script>
(function ($) {
    if (!$) return;

$(document).ready(function(){

    // আটকে থাকা modal backdrop / scroll লক সরানো
    $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
    $('.modal-backdrop').remove();
    document.querySelectorAll('.modal.show').forEach(function (m) {
        m.classList.remove('show');
        m.style.display = '';
        m.setAttribute('aria-hidden', 'true');
    });

    // Order Note / Admin Note popup open
    $(document).on('click', '.note-modal-btn', function (e) {
        e.preventDefault();
        let orderId = $(this).data('id');
        let type    = $(this).data('type');
        let note    = $(this).data('note') || '';

        $('#note_order_id').val(orderId);
        $('#note_type').val(type);
        $('#note_modal_text').val(note);

        if (type === 'admin') {
            $('#noteModalLabel').text('Admin Note');
            $('#note_label').text('Admin Note');
        } else {
            $('#noteModalLabel').text('Order Note (Customer)');
            $('#note_label').text('Order Note (Customer)');
        }

        $('#noteModal').modal('show');
    });

    // Save Note (AJAX)
    $('#saveNoteBtn').on('click', function () {
        let orderId = $('#note_order_id').val();
        let type    = $('#note_type').val();
        let note    = $('#note_modal_text').val();

        $.ajax({
            url: "{{ route('admin.order.update_note') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                order_id: orderId,
                note_type: type,
                note: note
            },
            success: function (res) {
                if (res.status === 'success') {
                    toastr.success('Note updated successfully');
                    let selector = '.note-modal-btn[data-id="' + orderId + '"][data-type="' + type + '"]';
                    let $btn = $(selector);
                    $btn.data('note', note);
                    $btn.text(note ? 'View' : 'Add');
                    $('#noteModal').modal('hide');
                } else {
                    toastr.error(res.message || 'Update failed');
                }
            },
            error: function () {
                toastr.error('Something went wrong');
            }
        });
    });

    // ── Row selection ──
    // Every order row has an enabled checkbox; the whole first cell toggles it, the header box selects the
    // current page (indeterminate when partly selected), Shift+click selects a range.
    var oiLastIndex = null;
    function oiBoxes() { return $('tbody input.checkbox'); }
    function oiSyncSelection() {
        var $all = oiBoxes(), n = $all.filter(':checked').length;
        $all.each(function () { $(this).closest('tr').toggleClass('oi-row-selected', this.checked); });
        var head = $('.checkall').get(0);
        if (head) {
            head.checked = n > 0 && n === $all.length;
            head.indeterminate = n > 0 && n < $all.length;
        }
        $('#oiSelCount').text(n);
        $('#oiSelectionBar').prop('hidden', n === 0);
    }
    window.oiClearSelection = function () {
        oiBoxes().prop('checked', false);
        oiLastIndex = null;
        oiSyncSelection();
    };
    function oiToggleRow(box, checked, shiftKey) {
        var $all = oiBoxes(), index = $all.index(box);
        if (shiftKey && oiLastIndex !== null && oiLastIndex !== index) {
            var from = Math.min(oiLastIndex, index), to = Math.max(oiLastIndex, index);
            $all.slice(from, to + 1).prop('checked', checked);
        } else {
            box.checked = checked;
        }
        oiLastIndex = index;
        oiSyncSelection();
    }
    // Click on the checkbox itself (the browser has already flipped it)
    $(document).on('click', 'tbody input.checkbox', function (e) {
        oiToggleRow(this, this.checked, e.shiftKey);
    });
    // Click anywhere else in the checkbox cell
    $(document).on('click', 'tbody td.oi-select-cell', function (e) {
        if ($(e.target).is('input')) return;
        var box = $(this).find('input.checkbox').get(0);
        if (box) oiToggleRow(box, !box.checked, e.shiftKey);
    });
    $(document).on('click', 'thead th.oi-select-cell', function (e) {
        if ($(e.target).is('input')) return;
        var head = $(this).find('.checkall').get(0);
        if (head) { head.checked = !head.checked; $(head).trigger('change'); }
    });
    $(document).on('change', '.checkall', function () {
        oiBoxes().prop('checked', this.checked);
        oiLastIndex = null;
        oiSyncSelection();
    });
    $(document).on('click', '#oiSelClear', function () { window.oiClearSelection(); });
    // A new page / filter / back-forward visit starts with nothing selected
    window.addEventListener('pageshow', function () { window.oiClearSelection(); });
    window.oiClearSelection();

    // ── অর্ডার কুইক ভিউ মডাল ──
    function oqvShowModal() {
        var el = document.getElementById('orderQuickViewModal');
        if (!el) return;
        $('.modal-backdrop').not('.oqv-temp-backdrop').remove();
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var inst = bootstrap.Modal.getOrCreateInstance(el);
            inst.show();
        } else if ($.fn.modal) {
            $(el).modal('show');
        }
    }

    $('#orderQuickViewModal').on('hidden.bs.modal', function () {
        $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
        $('.modal-backdrop').remove();
        $('#orderQuickViewBody').html(
            '<div class="oqv-loading"><div class="spinner-border text-primary" role="status"></div><p class="mt-3 mb-0">লোড হচ্ছে...</p></div>'
        );
    });

    function oqvUpdateFraudBadge(rate, isFraud) {
        var $box = $('#oqvFraudStatus');
        if (!$box.length) return;
        if (isFraud) {
            $box.html('<span class="badge bg-danger oqv-fraud-badge">ফ্রড (ঝুঁকি)</span>');
            return;
        }
        if (rate >= 80) {
            $box.html('<span class="badge bg-success oqv-fraud-badge">' + rate + '% নিরাপদ</span>');
        } else {
            $box.html('<span class="badge bg-danger oqv-fraud-badge">' + rate + '% ঝুঁকি</span>');
        }
    }

    $(document).on('click', '.order-quick-view-btn', function () {
        var orderId = $(this).data('order-id');
        if (!orderId) return;
        $('#orderQuickViewBody').html(
            '<div class="oqv-loading"><div class="spinner-border text-primary"></div><p class="mt-3 mb-0">লোড হচ্ছে...</p></div>'
        );
        $('#oqvModalInvoice').text('');
        oqvShowModal();
        $.get('{{ url("admin/order/quick-view") }}/' + orderId, function (res) {
            if (res.status === 'success') {
                $('#orderQuickViewBody').html(res.html);
                $('#oqvModalInvoice').text('#' + res.invoice_id);
            } else {
                $('#orderQuickViewBody').html('<div class="alert alert-danger">ডাটা লোড করা যায়নি</div>');
            }
        }).fail(function () {
            $('#orderQuickViewBody').html('<div class="alert alert-danger">সার্ভার এরর — আবার চেষ্টা করুন</div>');
        });
    });

    // ── Courier tracking box (quick view) ──
    var cptToken = $('meta[name="csrf-token"]').attr('content');
    function cptEsc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    $(document).on('click', '.cpt-copy', function () {
        var text = String($(this).data('copy'));
        var done = function () { toastr.success('কপি হয়েছে'); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var $t = $('<input>').val(text).appendTo('body').select();
            document.execCommand('copy'); $t.remove(); done();
        }
    });

    $(document).on('click', '.cpt-refresh', function () {
        var $btn = $(this), $box = $btn.closest('.cpt-box');
        $btn.prop('disabled', true).find('i').addClass('fa-spin');
        $.post($btn.data('url'), { _token: cptToken }, function (res) {
            if (res.html) { $box.replaceWith(res.html); }
            (res.ok ? toastr.success : toastr.warning)(res.message);
            if (res.order_status_changed) { toastr.info('অর্ডার স্ট্যাটাস আপডেট হয়েছে — পেজ রিফ্রেশ করুন'); }
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'রিফ্রেশ করা যায়নি');
        }).always(function () {
            $btn.prop('disabled', false).find('i').removeClass('fa-spin');
        });
    });

    $(document).on('click', '.cpt-link-toggle', function () {
        $(this).siblings('.cpt-link-form').slideToggle(150);
    });

    // Any change to the courier or code needs a fresh check before linking
    $(document).on('input change', '.cpt-link-courier, .cpt-link-code', function () {
        var $form = $(this).closest('.cpt-link-form');
        $form.find('.cpt-link-confirm').hide();
        $form.find('.cpt-link-result').empty();
    });

    function cptLinkData($form) {
        return {
            order_id: $form.closest('.cpt-box').data('order-id'),
            courier: $form.find('.cpt-link-courier').val(),
            code: $.trim($form.find('.cpt-link-code').val())
        };
    }

    $(document).on('click', '.cpt-link-check', function () {
        var $btn = $(this), $form = $btn.closest('.cpt-link-form'), data = cptLinkData($form);
        if (!data.code) { toastr.error('Consignment ID দিন'); return; }
        $btn.prop('disabled', true).text('যাচাই হচ্ছে...');
        $form.find('.cpt-link-confirm').hide();
        $.get($btn.data('url'), data, function (res) {
            var html = '<div class="alert alert-light border">'
                + '<strong>' + cptEsc(res.courier) + '</strong>: '
                + (res.status ? '<span class="badge bg-' + cptEsc(res.tone) + '">' + cptEsc(res.status) + '</span>' : '<span class="text-muted">স্ট্যাটাস পাওয়া যায়নি</span>')
                + (res.details.recipient ? '<br>প্রাপক: ' + cptEsc(res.details.recipient) : '')
                + (res.details.cod ? '<br>কুরিয়ার COD: ' + cptEsc(res.details.cod) : '')
                + (res.details.events ? '<br>আগে থেকে জমা থাকা আপডেট: ' + cptEsc(res.details.events) + ' টি' : '')
                + '</div>';
            $.each(res.warnings || [], function (_, w) { html += '<div class="alert alert-warning">' + cptEsc(w) + '</div>'; });
            $form.find('.cpt-link-result').html(html);
            $form.find('.cpt-link-confirm').show();
        }).fail(function (xhr) {
            $form.find('.cpt-link-result').html('<div class="alert alert-danger">' + cptEsc((xhr.responseJSON && xhr.responseJSON.message) || 'যাচাই করা যায়নি') + '</div>');
        }).always(function () {
            $btn.prop('disabled', false).text('যাচাই করুন');
        });
    });

    $(document).on('click', '.cpt-link-confirm', function () {
        var $btn = $(this), $form = $btn.closest('.cpt-link-form'), data = cptLinkData($form);
        data._token = cptToken;
        $btn.prop('disabled', true);
        $.post($btn.data('url'), data, function (res) {
            toastr.success(res.message);
            var orderId = data.order_id;
            // Reload the quick view so the box shows the linked parcel
            $.get('{{ url("admin/order/quick-view") }}/' + orderId, function (r) {
                if (r.status === 'success') { $('#orderQuickViewBody').html(r.html); }
            });
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'লিংক করা যায়নি');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.fraud-check-oqv', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var mobile = $(this).data('mobile');
        if (!mobile) { toastr.error('মোবাইল নম্বর নেই'); return; }
        var $btn = $(this);
        var $report = $('#oqvFraudReport');
        $btn.prop('disabled', true);
        $('#oqvFraudStatus').html('<span class="badge bg-secondary">চেক হচ্ছে...</span>');
        $report.html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status"></div>' +
            '<p class="mt-2 mb-0 small text-muted">কুরিয়ার ডাটা যাচাই হচ্ছে...</p></div>'
        ).show();
        $.ajax({
            url: "{{ route('admin.fraud.check') }}",
            type: 'POST',
            data: { mobile: mobile, _token: "{{ csrf_token() }}" },
            timeout: 60000,
            success: function (res) {
                $btn.prop('disabled', false);
                if (res && res.status === 'success') {
                    if (res.data && res.data.is_fraud === true) {
                        oqvUpdateFraudBadge(0, true);
                        $report.html(
                            '<div class="alert alert-danger mb-0 text-center">' +
                            '<h6 class="mb-1"><i class="fas fa-exclamation-triangle"></i> ফ্রড ডিটেক্টেড</h6>' +
                            '<p class="mb-0 small">মোবাইল: ' + mobile + '</p></div>'
                        ).show();
                        $('.fraud-check[data-mobile="' + mobile + '"]').not('.fraud-check-oqv')
                            .removeClass('btn-warning btn-success').addClass('btn-danger').text('ফ্রড (ঝুঁকি)');
                        toastr.warning('ফ্রড ডিটেক্টেড');
                        return;
                    }
                    var apiData = (res.data && res.data.data) ? res.data.data : (res.data || {});
                    var summary = buildSummary(apiData);
                    oqvUpdateFraudBadge(summary.rate, false);
                    $report.html(loadFraudHtml(summary, mobile)).show();
                    var allBtns = $('.fraud-check[data-mobile="' + mobile + '"]').not('.fraud-check-oqv');
                    allBtns.removeClass('btn-warning btn-success btn-danger');
                    if (summary.rate >= 80) {
                        allBtns.addClass('btn-success').text(summary.rate + '% নিরাপদ');
                    } else {
                        allBtns.addClass('btn-danger').text(summary.rate + '% ঝুঁকি');
                    }
                    var $mb = $('#orderQuickViewBody');
                    if ($report.length && $mb.length) {
                        $mb.animate({
                            scrollTop: $report.offset().top - $mb.offset().top + $mb.scrollTop() - 16
                        }, 300);
                    }
                    toastr.success('ফ্রড চেক সম্পন্ন');
                } else {
                    $report.html('<div class="alert alert-danger mb-0">' + ((res && res.message) ? res.message : 'ফ্রড চেক ব্যর্থ') + '</div>').show();
                    toastr.error((res && res.message) ? res.message : 'ফ্রড চেক ব্যর্থ');
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'ফ্রড চেক ব্যর্থ';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                $report.html('<div class="alert alert-danger mb-0">' + msg + '</div>').show();
                toastr.error(msg);
            }
        });
    });

    $(document).on('click', '.oi-quick-courier', function () {
        var slug = $(this).data('courier');
        var orderId = $(this).data('order-id');
        var $btn = $(this);
        if (!orderId || !slug) return;
        if (slug === 'steadfast') {
            var qv = document.getElementById('orderQuickViewModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) { bootstrap.Modal.getOrCreateInstance(qv).hide(); } else { $(qv).modal('hide'); }
            oiSteadfastSend([String(orderId)], null);
            return;
        }
        if (!confirm('এই অর্ডার ' + slug.toUpperCase() + ' কুরিয়ারে পাঠাতে চান?')) return;
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        $.get('{{ url("admin/bulk-courier") }}/' + slug + '?status=5', { order_ids: [orderId] }, function (res) {
            if (res.status === 'success') {
                toastr.success(res.message || 'কুরিয়ারে পাঠানো হয়েছে');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                toastr.error(res.message || 'ব্যর্থ');
                $btn.prop('disabled', false).html('<i class="fas fa-truck"></i> ' + slug.charAt(0).toUpperCase() + slug.slice(1));
            }
        }).fail(function () {
            toastr.error('কুরিয়ার রিকোয়েস্ট ব্যর্থ');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '.oi-quick-pathao', function () {
        var orderId = $(this).data('order-id');
        $('#pathao_order_ids').val(orderId);
        window.oiPathaoSingle = true;
        var qvEl = document.getElementById('orderQuickViewModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(qvEl).hide();
        } else {
            $(qvEl).modal('hide');
        }
        $('#pathao').modal('show');
    });

    // Fraud check → Popup Modal Open
    $(document).on('click', '.fraud-check', function(e){
        e.preventDefault();
        let mobile  = $(this).data('mobile');
        
        if (!mobile) { return toastr.error("No mobile number found"); }

        $("#fraudModalBody").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                <p class="mt-3 fw-bold">তথ্য যাচাই করা হচ্ছে...</p>
            </div>
        `);

        $("#fraudCheckModal").modal("show");

        $.ajax({
            url: "{{ route('admin.fraud.check') }}",
            type: "POST",
            data: { 
                mobile: mobile,
                // আমরা এখানে order_id পাঠাচ্ছি না, কারণ কন্ট্রোলার মোবাইল নম্বর দিয়ে 
                // সব অর্ডার আপডেট করবে।
                _token: "{{ csrf_token() }}" 
            },
            timeout: 60000, // 60 seconds timeout
            beforeSend: function() {
                // Show loading state
                $("#fraudModalBody").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">ফ্রড চেক করা হচ্ছে... অনুগ্রহ করে অপেক্ষা করুন।</p>
                    </div>
                `);
            },
            success: function(res) {
                
                if (res && res.status === "success") {
                    let apiData = {};
                    
                    if(res.data && res.data.data) {
                        apiData = res.data.data;
                    } else if (res.data) {
                        apiData = res.data;
                    }

                    // এখন আমরা পেইজে থাকা ওই মোবাইল নাম্বারের *সব বাটন* খুঁজে বের করব
                    let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');

                    if(res.data && res.data.is_fraud === true) {
                         $("#fraudModalBody").html(`
                            <div class="alert alert-danger text-center p-5">
                                <h3>⚠️ ফ্রড ডিটেক্টেড!</h3>
                                <p>এই নাম্বারটি ফ্রড তালিকায় রয়েছে।</p>
                            </div>
                         `);
                         
                         // সব বাটন লাল করে দেওয়া
                         allBtns.removeClass('btn-warning text-dark btn-success').addClass('btn-danger').text('ফ্রড (ঝুঁকি)');
                         return;
                    }

                    // Build Summary
                    var summary = buildSummary(apiData);
                    $("#fraudModalBody").html(loadFraudHtml(summary, mobile));

                    // ==========================================
                    // INSTANT BUTTON UPDATE LOGIC (ALL BUTTONS)
                    // ==========================================
                    let r = summary.rate;
                    
                    // আগের ক্লাস রিমুভ
                    allBtns.removeClass('btn-warning text-dark btn-success btn-danger');

                    if(r >= 80) {
                        // Safe
                        allBtns.addClass('btn-success');
                        allBtns.text(r + '% নিরাপদ');
                    } else {
                        // Risk
                        allBtns.addClass('btn-danger');
                        allBtns.text(r + '% ঝুঁকি');
                    }

                    toastr.success('স্ট্যাটাস সফলভাবে সেভ হয়েছে!');

                } else {
                    var msg = (res && res.message) ? res.message : 'No data returned';
                    $("#fraudModalBody").html(`<div class="alert alert-danger text-center p-4">${msg}</div>`);
                }
            },

            error: function(xhr, status, error) {
                console.error('Fraud Check AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseJSON,
                    statusCode: xhr.status
                });
                
                let errorMessage = 'অনুগ্রহ করে আবার চেষ্টা করুন।';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = 'Request timeout! API server response নেওয়া যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।';
                } else if (status === 'error') {
                    errorMessage = 'Connection error! API server-এ connection করতে পারছে না।';
                } else if (xhr.status === 400) {
                    errorMessage = 'Invalid request! দয়া করে মোবাইল নাম্বার চেক করুন।';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error! দয়া করে admin-কে জানান।';
                } else if (xhr.status === 404) {
                    errorMessage = 'API endpoint not found!';
                }
                
                $("#fraudModalBody").html(`
                    <div class="alert alert-danger text-center p-4">
                        <h5>❌ Error!</h5>
                        <p>${errorMessage}</p>
                        ${xhr.responseJSON && xhr.responseJSON.message ? `<small>${xhr.responseJSON.message}</small>` : ''}
                    </div>
                `);
                
                // Reset button to original state
                let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');
                allBtns.removeClass('btn-success btn-danger').addClass('btn-warning').text('চেকিং');
                
                toastr.error('Fraud check failed: ' + errorMessage);
            }
        });
    });

    // order assign
    $(document).on('submit', 'form#order_assign', function(e){
        e.preventDefault();
        var url = $(this).attr('action');
        let user_id = $('#user_id').val();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }

        $.ajax({
           type: 'GET',
           url: url,
           data: { user_id: user_id, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   window.location.reload();
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // order status change
    $(document).on('submit', 'form#order_status_form', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        var url = $(this).attr('action');
        let order_status = $('#order_status').val();
        var $statusSelect = $('#order_status');
        var $statusError = $('#status_error');
        
        // Clear any previous validation state
        $statusSelect.removeClass('is-invalid is-valid');
        $statusError.hide();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        // Validate orders selected FIRST
        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return false;
        }
        
        // Validate status selected - check multiple conditions
        var statusValue = String(order_status || '').trim();
        if(!statusValue || statusValue === '' || statusValue === 'null' || statusValue === 'undefined' || statusValue === '0'){
            $statusSelect.addClass('is-invalid');
            $statusError.text('Please select a status').show();
            toastr.error('Please Select A Status First !');
            // Focus on select field and scroll to it
            $statusSelect.focus();
            $('html, body').animate({
                scrollTop: $statusSelect.offset().top - 100
            }, 300);
            return false;
        }
        
        // Additional check - make sure it's a valid number
        if(isNaN(parseInt(statusValue)) || parseInt(statusValue) <= 0){
            $statusSelect.addClass('is-invalid');
            $statusError.text('Please select a valid status').show();
            toastr.error('Please Select A Valid Status !');
            $statusSelect.focus();
            return false;
        }

        // Show loading
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fe-loader"></i> Updating...');

        $.ajax({
           type: 'GET',
           url: url,
           data: { order_status: order_status, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   $('#changeStatus').modal('hide');
                   setTimeout(function(){
                       window.location.reload();
                   }, 1000);
               } else {
                   toastr.error(res.message || 'Failed something wrong');
                   $submitBtn.prop('disabled', false).html(originalHtml);
               }
           },
           error: function(xhr){
               console.error('Status update error:', xhr);
               var errorMsg = 'Something went wrong';
               
               // Handle Laravel validation errors
               if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                   var errors = xhr.responseJSON.errors;
                   if(errors.order_status){
                       $statusSelect.addClass('is-invalid');
                       $statusError.text(errors.order_status[0]).show();
                       errorMsg = errors.order_status[0];
                   } else if(errors.order_ids){
                       errorMsg = errors.order_ids[0];
                   }
               } else if(xhr.responseJSON && xhr.responseJSON.message){
                   errorMsg = xhr.responseJSON.message;
               } else if(xhr.status === 400){
                   errorMsg = 'Bad request. Please check your selection.';
               }
               
               toastr.error(errorMsg);
               $submitBtn.prop('disabled', false).html(originalHtml);
           }
        });
        
        return false;
    });

    // order delete (bulk)
    $(document).on('click', '.order_delete', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }
        // Manual invoices are never deleted (the server refuses too) — cancel them instead.
        var manual = $('input.checkbox:checked[data-manual="1"]').map(function(){ return '#' + $(this).data('invoice'); }).get();
        if(manual.length){
            toastr.error('ম্যানুয়াল ইনভয়েস ডিলিট করা যায় না, বাতিল (Cancel) করুন: ' + manual.join(', '));
            return;
        }

        $.ajax({
           type: 'GET',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.message);
                   window.location.reload();
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // multiple print
    $(document).on('click', '.multi_order_print', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select Atleast One Order!');
            return;
        }
        $.ajax({
           type: 'GET',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   var myWindow = window.open("", "_blank");
                   myWindow.document.write(res.view);
                   window.oiClearSelection();
               } else {
                   toastr.error(res.message || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // label print
    $(document).on('click', '.multi_label_print', function(e){
        e.preventDefault();
        var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
        if(order_ids.length == 0){ toastr.error('Please Select Atleast One Order!'); return; }
        $.ajax({
            type: 'GET',
            url: $(this).attr('href'),
            data: { order_ids: order_ids, type: 'label' },
            success: function(res){
                if(res.status == 'success'){
                    var w = window.open("","_blank");
                    w.document.write(res.view);
                    window.oiClearSelection();
                } else { toastr.error(res.message || 'Failed'); }
            },
            error: function(){ toastr.error('Something went wrong'); }
        });
    });

    // ── Bulk courier (Steadfast / RedX / Pathao) ──
    // Same rules as the server (CourierDispatchService::cannotSend): never re-send an order that already has a
    // courier id, never send Delivered (6) / Cancelled (11) orders, and the shipping phone must be 11 digits.
    // ids: the orders about to be sent (default: the ticked rows)
    function oiCourierPlan(ids) {
        var send = [], skip = [];
        var $boxes = ids ? oiBoxes().filter(function () { return ids.indexOf(String(this.value)) !== -1; })
                         : oiBoxes().filter(':checked');
        $boxes.each(function () {
            var $b = $(this), status = parseInt($b.data('status'), 10);
            var phone = String($b.data('phone') || '').replace(/\D+/g, '').slice(-11);
            var row = {
                id: $b.val(), invoice: String($b.data('invoice')), name: String($b.data('name') || ''),
                phone: String($b.data('phone') || ''), cod: parseFloat($b.data('cod')) || 0
            };
            var reason = null;
            if (String($b.data('courier-id') || '') !== '') {
                reason = 'আগেই ' + ($b.data('courier') || 'কুরিয়ার') + ' এ পাঠানো (' + $b.data('courier-id') + ')';
            } else if (status === 6) {
                reason = 'ডেলিভারড অর্ডার';
            } else if (status === 11) {
                reason = 'বাতিল অর্ডার';
            } else if (phone.length !== 11 || phone.charAt(0) !== '0') {
                reason = 'ফোন নম্বর সঠিক নয় (১১ ডিজিট লাগবে)';
            }
            if (reason) { row.reason = reason; skip.push(row); } else { send.push(row); }
        });
        return { send: send, skip: skip };
    }
    function oiMoney(n) { return '৳' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function oiPlanHtml(courier, plan) {
        var h = '<div class="oi-courier-confirm">';
        if (plan.send.length) {
            h += '<div><strong>' + plan.send.length + ' টি অর্ডার ' + cptEsc(courier) + ' এ যাবে:</strong></div>'
               + '<table><thead><tr><th>ইনভয়েস</th><th>গ্রাহক</th><th>ফোন</th><th>COD</th></tr></thead><tbody>';
            $.each(plan.send, function (_, r) {
                h += '<tr><td>#' + cptEsc(r.invoice) + '</td><td>' + cptEsc(r.name) + '</td><td>' + cptEsc(r.phone) + '</td><td>' + oiMoney(r.cod) + '</td></tr>';
            });
            h += '</tbody></table>';
        }
        if (plan.skip.length) {
            h += '<div class="oi-skip"><strong>' + plan.skip.length + ' টি বাদ যাবে:</strong><ul class="mb-0 ps-3">';
            $.each(plan.skip, function (_, r) { h += '<li>#' + cptEsc(r.invoice) + ' — ' + cptEsc(r.reason) + '</li>'; });
            h += '</ul></div>';
        }
        return h + '</div>';
    }
    // Ask before sending; resolves with the order ids to send (empty = cancelled / nothing to send).
    function oiConfirmCourier(courier, ids) {
        var plan = oiCourierPlan(ids);
        if (!plan.send.length && !plan.skip.length) {
            toastr.error('কমপক্ষে একটি অর্ডার সিলেক্ট করুন (Select at least one order first)');
            return $.Deferred().resolve([]).promise();
        }
        if (!plan.send.length) {
            Swal.fire({ title: cptEsc(courier) + ' — পাঠানোর মতো অর্ডার নেই', html: oiPlanHtml(courier, plan), icon: 'info', width: 640 });
            return $.Deferred().resolve([]).promise();
        }
        var d = $.Deferred();
        Swal.fire({
            title: cptEsc(courier) + ' এ পাঠাবেন?',
            html: oiPlanHtml(courier, plan),
            icon: 'question', width: 640,
            showCancelButton: true,
            confirmButtonText: 'হ্যাঁ, ' + plan.send.length + ' টি পাঠান',
            cancelButtonText: 'বাতিল'
        }).then(function (r) { d.resolve(r.isConfirmed ? plan.send.map(function (x) { return x.id; }) : []); });
        return d.promise();
    }
    // Per-order result, then reload so sent orders show their courier info.
    function oiShowCourierResult(courier, res) {
        var h = '<div class="oi-courier-confirm">';
        var sent = res.sent || (res.success || []).map(function (inv) { return { order_id: inv }; });
        $.each(sent, function (_, s) {
            h += '<div class="oi-ok">✔ #' + cptEsc(s.order_id) + ' — পাঠানো হয়েছে' + (s.consignment_id ? ' (Consignment ' + cptEsc(s.consignment_id) + ')' : '') + '</div>';
        });
        $.each(res.failed || [], function (_, f) {
            h += '<div class="oi-err">✖ #' + cptEsc(f.order_id) + ' — ' + cptEsc(f.message) + '</div>';
        });
        if (!sent.length && !(res.failed || []).length) { h += '<div>' + cptEsc(res.message || '') + '</div>'; }
        if (sent.length && res.pickup && !res.pickup.done) {
            h += '<div class="oi-pickup">আজ ' + res.pickup.count + ' টি পার্সেল Steadfast এ পাঠানো হয়েছে — Steadfast পোর্টালে পিকআপ রিকোয়েস্ট দিন।'
               + (res.pickup.portal_url ? ' <a href="' + cptEsc(res.pickup.portal_url) + '" target="_blank" rel="noopener">Steadfast পোর্টাল খুলুন ↗</a>' : '') + '</div>';
        }
        h += '</div>';
        window.oiClearSelection();
        Swal.fire({ title: cptEsc(courier) + ' — ফলাফল', html: h, icon: sent.length ? 'success' : 'error', width: 640 })
            .then(function () { window.location.reload(); });
    }

    // ── Steadfast: send dialog with parcel weight (+ number of packages) per order ──
    // The server checks everything again (CourierDispatchService); this only collects and pre-checks the inputs.
    var OI_SF_PREVIEW = @json(route('admin.courier_send.preview'));
    var OI_SF_SEND = @json(route('admin.bulk_courier', 'steadfast')) + '?status=5';
    function oiSfDigits(v) {
        return String(v == null ? '' : v).trim().replace(/[০-৯]/g, function (d) { return '০১২৩৪৫৬৭৮৯'.indexOf(d); }).replace(',', '.');
    }
    function oiSfWeightError(v, st) {
        v = oiSfDigits(v);
        if (v === '') { return st.weight_required ? 'ওজন দিন' : null; }
        if (!/^\d+(\.\d{1,2})?$/.test(v)) { return 'সংখ্যা দিন, যেমন 1.5'; }
        if (parseFloat(v) <= 0) { return '০ এর বেশি দিন'; }
        if (parseFloat(v) > st.weight_max) { return 'সর্বোচ্চ ' + st.weight_max + ' kg'; }
        return null;
    }
    function oiSfHtml(res, send, skip) {
        var st = res.settings, packages = st.lot_mode === 'packages';
        var unmatched = send.filter(function (o) { return st.area_on && !o.area; }).length;
        var h = '<div class="oi-courier-confirm"><div><strong>' + send.length + ' টি অর্ডার Steadfast এ যাবে:</strong></div>'
              + '<div class="oi-sf-wrap"><table class="oi-sf-table"><thead><tr><th>ইনভয়েস</th><th>গ্রাহক</th><th>COD</th>'
              + (st.area_on ? '<th>Steadfast এরিয়া</th>' : '')
              + '<th>ওজন (kg)' + (st.weight_required ? ' *' : '') + '</th><th>' + (packages ? 'প্যাকেজ' : 'আইটেম (total_lot)') + '</th></tr></thead><tbody>';
        $.each(send, function (_, o) {
            h += '<tr data-id="' + o.id + '"><td>#' + cptEsc(o.invoice) + '</td><td>' + cptEsc(o.name) + '<br><small>' + cptEsc(o.phone) + '</small></td><td>' + oiMoney(o.cod) + '</td>';
            if (st.area_on) {
                h += o.area
                    ? '<td class="oi-sf-area-ok">✓ ' + cptEsc(o.area) + '</td>'
                    : '<td class="oi-sf-area-warn">⚠ ' + (o.area_problem === 'close' ? 'যাচাই বাকি' : (o.area_problem === 'not_synced' ? 'এরিয়া সিঙ্ক হয়নি' : 'মেলানো হয়নি'))
                      + '<br><small>আগের মতো যাবে: ' + cptEsc(o.local_area || '—') + '</small></td>';
            }
            h += '<td><input type="text" inputmode="decimal" class="oi-sf-weight" aria-label="ওজন (kg) #' + cptEsc(o.invoice) + '" value="' + cptEsc(o.weight || '') + '" placeholder="kg">'
               + '<div class="oi-sf-picks">' + ['0.5', '1', '1.5', '2'].map(function (w) { return '<button type="button" data-w="' + w + '">' + w + '</button>'; }).join('') + '</div>'
               + '<div class="oi-sf-err"></div></td>';
            h += packages
                ? '<td><input type="number" class="oi-sf-lot" min="1" max="999" step="1" value="1" aria-label="প্যাকেজ #' + cptEsc(o.invoice) + '"></td>'
                : '<td>' + o.qty + '</td>';
            h += '</tr>';
        });
        h += '</tbody></table></div>';
        if (skip.length) {
            h += '<div class="oi-skip"><strong>' + skip.length + ' টি বাদ যাবে:</strong><ul class="mb-0 ps-3">';
            $.each(skip, function (_, o) { h += '<li>#' + cptEsc(o.invoice) + ' — ' + cptEsc(o.reason) + '</li>'; });
            h += '</ul></div>';
        }
        if (unmatched) {
            h += '<div class="oi-sf-warn">⚠ ' + unmatched + ' টি অর্ডারের থানা Steadfast এরিয়ার সাথে মেলানো হয়নি — এগুলো আগের মতো বাংলা ঠিকানায় যাবে। পাঠানো যাবে। '
               + '<a href="' + cptEsc(res.areas_url) + '" target="_blank" rel="noopener">এরিয়া মেলান ↗</a></div>';
        }
        h += '<div class="oi-sf-info">' + (st.weight_in_note ? 'ওজন নোটের শেষে যাবে ("Wt: 1.5 kg") — Steadfast API তে ওজনের আলাদা ঘর নেই। ' : '')
           + (packages ? 'total_lot = প্যাকেজ সংখ্যা।' : 'total_lot = মোট আইটেম সংখ্যা।') + '</div>';
        return h + '</div>';
    }
    function oiSteadfastSend(ids, $btn) {
        if (!ids.length) {
            toastr.error('কমপক্ষে একটি অর্ডার সিলেক্ট করুন (Select at least one order first)');
            return;
        }
        if (oiCourierBusy) return;
        $.get(OI_SF_PREVIEW, { order_ids: ids }).done(function (res) {
            var send = (res.orders || []).filter(function (o) { return !o.reason; });
            var skip = (res.orders || []).filter(function (o) { return o.reason; });
            if (!send.length) {
                var h = '<div class="oi-courier-confirm"><ul class="mb-0 ps-3">';
                $.each(skip, function (_, o) { h += '<li>#' + cptEsc(o.invoice) + ' — ' + cptEsc(o.reason) + '</li>'; });
                Swal.fire({ title: 'Steadfast — পাঠানোর মতো অর্ডার নেই', html: h + '</ul></div>', icon: 'info', width: 640 });
                return;
            }
            var st = res.settings;
            Swal.fire({
                title: 'Steadfast এ পাঠাবেন?',
                html: oiSfHtml(res, send, skip),
                width: 920,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: 'হ্যাঁ, ' + send.length + ' টি পাঠান',
                cancelButtonText: 'বাতিল',
                didOpen: function (popup) {
                    var $p = $(popup);
                    $p.on('click', '.oi-sf-picks button', function () {
                        $(this).closest('td').find('.oi-sf-weight').val($(this).data('w')).removeClass('is-bad').trigger('input');
                    });
                    $p.on('input', '.oi-sf-weight', function () {
                        $(this).removeClass('is-bad').closest('td').find('.oi-sf-err').text('');
                    });
                    var $first = $p.find('.oi-sf-weight').filter(function () { return !this.value; }).first();
                    if ($first.length) { $first.trigger('focus'); }
                },
                preConfirm: function () {
                    var weights = {}, lots = {}, bad = 0;
                    $(Swal.getPopup()).find('tr[data-id]').each(function () {
                        var $tr = $(this), id = $tr.data('id'), $w = $tr.find('.oi-sf-weight');
                        var err = oiSfWeightError($w.val(), st);
                        $w.toggleClass('is-bad', !!err);
                        $tr.find('.oi-sf-err').text(err || '');
                        if (err) { bad++; return; }
                        if (oiSfDigits($w.val()) !== '') { weights[id] = oiSfDigits($w.val()); }
                        var $lot = $tr.find('.oi-sf-lot');
                        if ($lot.length) {
                            var n = parseInt($lot.val(), 10);
                            if (!(n >= 1 && n <= 999)) { $lot.addClass('is-bad'); bad++; return; }
                            lots[id] = n;
                        }
                    });
                    if (bad) {
                        Swal.showValidationMessage(bad + ' টি অর্ডারের ওজন/প্যাকেজ ঠিক করুন');
                        return false;
                    }
                    return { weights: weights, lots: lots };
                }
            }).then(function (r) {
                if (!r.isConfirmed) return;
                oiCourierBusy = true;
                var originalHtml = $btn ? $btn.html() : null;
                if ($btn) { $btn.addClass('disabled').attr('aria-disabled', 'true').html('<i class="fas fa-spinner fa-spin"></i> পাঠানো হচ্ছে...'); }
                $('.multi_order_courier, .oi-btn-pathao').addClass('disabled');
                $.ajax({ type: 'GET', url: OI_SF_SEND, data: { order_ids: send.map(function (o) { return o.id; }), weights: r.value.weights, lots: r.value.lots } })
                    .done(function (out) { oiShowCourierResult('Steadfast', out); })
                    .fail(function (xhr) {
                        var out = xhr.responseJSON || {};
                        if ((out.failed && out.failed.length) || out.sent) { oiShowCourierResult('Steadfast', out); }
                        else { Swal.fire({ title: 'Steadfast', text: out.message || 'সার্ভার এরর — আবার চেষ্টা করুন', icon: 'error' }); }
                    })
                    .always(function () {
                        oiCourierBusy = false;
                        if ($btn) { $btn.removeClass('disabled').removeAttr('aria-disabled').html(originalHtml); }
                        $('.multi_order_courier, .oi-btn-pathao').removeClass('disabled');
                    });
            });
        }).fail(function (xhr) {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'অর্ডারের তথ্য লোড করা যায়নি');
        });
    }

    var oiCourierBusy = false;
    $(document).on('click', '.multi_order_courier', function(e){
        e.preventDefault();
        if (oiCourierBusy) return;
        var $btn = $(this), url = $btn.attr('href'), courier = $.trim($btn.text());
        if ($btn.data('courier') === 'steadfast') {
            oiSteadfastSend(oiBoxes().filter(':checked').map(function () { return String(this.value); }).get(), $btn);
            return;
        }

        oiConfirmCourier(courier).then(function (ids) {
            if (!ids.length) return;
            oiCourierBusy = true;
            var originalHtml = $btn.html();
            $btn.addClass('disabled').attr('aria-disabled', 'true').html('<i class="fas fa-spinner fa-spin"></i> পাঠানো হচ্ছে...');
            $('.multi_order_courier, .oi-btn-pathao').addClass('disabled');

            $.ajax({ type: 'GET', url: url, data: { order_ids: ids } })
                .done(function (res) { oiShowCourierResult(courier, res); })
                .fail(function (xhr) {
                    var res = xhr.responseJSON || {};
                    if ((res.failed && res.failed.length) || res.sent) {
                        oiShowCourierResult(courier, res);
                    } else {
                        Swal.fire({ title: courier, text: res.message || 'সার্ভার এরর — আবার চেষ্টা করুন', icon: 'error' });
                    }
                })
                .always(function () {
                    oiCourierBusy = false;
                    $btn.removeClass('disabled').removeAttr('aria-disabled').html(originalHtml);
                    $('.multi_order_courier, .oi-btn-pathao').removeClass('disabled');
                });
        });
    });

    // Quick IP Block from order page
    $(document).on('click', '.block-ip-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var ip = $btn.data('ip');
        var reason = $btn.data('reason') || 'ফেইক অর্ডার';
        
        if(!ip){
            toastr.error('IP address not found');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<i class="fe-loader"></i> Blocking...');
        
        $.ajax({
            url: "{{ route('customers.ipblock.quick') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ip: ip,
                reason: reason
            },
            success: function(res){
                if(res.status === 'success'){
                    toastr.success(res.message || 'IP blocked successfully');
                    // Change button to show blocked state (badge style)
                    $btn.replaceWith('<span class="badge bg-secondary" title="This IP is already blocked"><i class="fe-shield"></i> Blocked</span>');
                } else {
                    toastr.error(res.message || 'Failed to block IP');
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                }
            },
            error: function(xhr){
                var errorMsg = 'Failed to block IP';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    });

    // Pathao Modal Open - Set selected order IDs
    // The toolbar button opens the dialog for the ticked orders; the quick view's single-order button
    // fills #pathao_order_ids itself (oiPathaoSingle). Checked when the dialog is about to open.
    window.oiPathaoSingle = false;
    $(document).on('show.bs.modal', '#pathao', function (e) {
        if (window.oiPathaoSingle) { window.oiPathaoSingle = false; return; }
        var plan = oiCourierPlan();
        if (!plan.send.length) {
            e.preventDefault();
            if (!plan.skip.length) {
                toastr.error('কমপক্ষে একটি অর্ডার সিলেক্ট করুন (Select at least one order first)');
            } else {
                Swal.fire({ title: 'Pathao — পাঠানোর মতো অর্ডার নেই', html: oiPlanHtml('Pathao', plan), icon: 'info', width: 640 });
            }
            return;
        }
        $('#pathao_order_ids').val(plan.send.map(function (x) { return x.id; }).join(','));
    });

    // Pathao City Change - Load Zones
    $(document).on('change', '#pathaocity', function(){
        var cityId = $(this).val();
        if(!cityId){
            $('#pathaozone').html('<option value="">Select Zone...</option>');
            $('#pathaoarea').html('<option value="">Select Area...</option>');
            return;
        }
        
        $.ajax({
            url: "{{ route('pathaocity') }}",
            type: "GET",
            data: { city_id: cityId },
            success: function(res){
                var options = '<option value="">Select Zone...</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, zone){
                        options += '<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>';
                    });
                } else {
                    toastr.warning('No zones found for this city');
                }
                $('#pathaozone').html(options);
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            },
            error: function(xhr){
                var errorMsg = 'Failed to load zones';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#pathaozone').html('<option value="">Select Zone...</option>');
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            }
        });
    });

    // Pathao Zone Change - Load Areas
    $(document).on('change', '#pathaozone', function(){
        var zoneId = $(this).val();
        if(!zoneId){
            $('#pathaoarea').html('<option value="">Select Area...</option>');
            return;
        }
        
        $.ajax({
            url: "{{ route('pathaozone') }}",
            type: "GET",
            data: { zone_id: zoneId },
            success: function(res){
                var options = '<option value="">Select Area...</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, area){
                        options += '<option value="' + area.area_id + '">' + area.area_name + '</option>';
                    });
                } else {
                    toastr.warning('No areas found for this zone');
                }
                $('#pathaoarea').html(options);
            },
            error: function(xhr){
                var errorMsg = 'Failed to load areas';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
                $('#pathaoarea').html('<option value="">Select Area...</option>');
            }
        });
    });

    // Pathao Form Submit
    var oiPathaoConfirmed = false;
    $(document).on('submit', '#order_sendto_pathao', function(e){
        e.preventDefault();
        var $form = $(this);

        var orderIds = $('#pathao_order_ids').val();
        if(!orderIds){
            toastr.error('Please select orders first');
            return;
        }
        if (oiCourierBusy) return;
        // Same confirmation as Steadfast/RedX, once per submit
        if (!oiPathaoConfirmed) {
            if(!$('#pathaostore').val() || !$('#pathaocity').val() || !$('#pathaozone').val() || !$('#pathaoarea').val()){
                toastr.error('Please fill all required fields (Store, City, Zone, Area)');
                return;
            }
            var pending = String(orderIds).split(',').map(function (id) { return $.trim(id); }).filter(Boolean);
            oiConfirmCourier('Pathao', pending).then(function (ids) {
                if (!ids.length) return;
                $('#pathao_order_ids').val(ids.join(','));
                oiPathaoConfirmed = true;
                $form.trigger('submit');
            });
            return;
        }
        oiPathaoConfirmed = false;
        oiCourierBusy = true;
        $form.find('[type=submit]').prop('disabled', true);

        var formData = $(this).serialize();
        formData += '&order_ids=' + orderIds.split(',').map(function(id){ return id.trim(); }).join(',');
        
        // Validate required fields
        if(!$('#pathaostore').val() || !$('#pathaocity').val() || !$('#pathaozone').val() || !$('#pathaoarea').val()){
            toastr.error('Please fill all required fields (Store, City, Zone, Area)');
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: "POST",
            data: formData,
            success: function(res){
                $('#pathao').modal('hide');
                oiShowCourierResult('Pathao', res);
            },
            error: function(xhr){
                var errorMsg = 'Failed to send orders';
                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            },
            complete: function(){
                oiCourierBusy = false;
                $form.find('[type=submit]').prop('disabled', false);
            }
        });
    });

});

})(window.jQuery);
</script>
@endsection
