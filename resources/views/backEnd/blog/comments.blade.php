@extends('backEnd.layouts.master')
@section('title','Blog Comments')
@section('css')
<style>
    .card {
        border: none;
        box-shadow: 0 0 20px rgba(18, 38, 63, 0.03);
        border-radius: 12px;
        overflow: hidden;
    }
    .card-body { padding: 25px; }
    .table thead th {
        background-color: #f9fbfd;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        color: #8391a2;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eef2f7;
        padding: 12px 15px;
    }
    .table tbody td {
        vertical-align: top;
        padding: 15px;
        border-bottom: 1px solid #f1f5f7;
        color: #313b5e;
        font-size: 14px;
    }
    .badge-pill { padding: 5px 10px; border-radius: 50rem; font-weight: 500; font-size: 11px; }
    .bc-tabs .nav-link { font-weight: 600; }
    .bc-tabs .badge { font-size: 11px; }
    .bc-author { font-weight: 600; color: #343a40; }
    .bc-email, .bc-meta { font-size: 12px; color: #7a6a5e; }
    .bc-body { white-space: pre-line; overflow-wrap: anywhere; max-width: 520px; }
    .bc-post { font-size: 13px; max-width: 220px; display: inline-block; }
    .bc-reply {
        margin-top: 10px;
        padding: 10px 12px;
        border-left: 3px solid var(--bilai-admin-orange, #e8861a);
        background: #fff8f0;
        border-radius: 0 8px 8px 0;
        font-size: 13px;
    }
    .bc-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
    .bc-actions form { margin: 0; }
</style>
@endsection

@section('content')
@php
    $tabs = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'spam'     => 'Spam',
        'all'      => 'All',
    ];
    $badgeClass = ['pending' => 'bg-warning text-dark', 'approved' => 'bg-success', 'spam' => 'bg-danger'];
@endphp
<div class="container-fluid">

    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="page-title mb-0" style="font-weight: 700; color: #2d3436;">Blog Comments</h4>
            <a href="{{ route('admin.blog.index') }}" class="btn btn-light rounded-pill border shadow-sm px-4">
                <i class="fe-list me-1"></i> All Blogs
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <ul class="nav nav-tabs bc-tabs mb-3">
                        @foreach($tabs as $key => $label)
                            @php $n = $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0); @endphp
                            <li class="nav-item">
                                <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                                   href="{{ route('admin.blog_comments.index', ['status' => $key]) }}">
                                    {{ $label }}
                                    <span class="badge {{ $key === 'pending' && $n > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-pill ms-1">{{ $n }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width: 190px;">Author</th>
                                    <th>Comment</th>
                                    <th style="width: 220px;">Post</th>
                                    <th style="width: 100px;">Status</th>
                                    <th class="text-end" style="width: 260px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($comments as $comment)
                                <tr>
                                    <td>
                                        <div class="bc-author">{{ $comment->name }}</div>
                                        <div class="bc-email">{{ $comment->email }}</div>
                                        <div class="bc-meta">{{ $comment->created_at->format('d M Y, h:i A') }}</div>
                                        @if($comment->ip_address)
                                            <div class="bc-meta">IP {{ $comment->ip_address }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Escaped plain text only — comments are never rendered as HTML here. --}}
                                        <div class="bc-body">{{ $comment->body }}</div>
                                        @foreach($comment->replies as $reply)
                                            <div class="bc-reply">
                                                <strong>{{ $reply->name }}</strong>
                                                <span class="badge bg-primary ms-1">Admin</span>
                                                <span class="bc-meta ms-1">{{ $reply->created_at->format('d M Y') }}</span>
                                                <div class="bc-body mt-1">{{ $reply->body }}</div>
                                                <form method="POST" action="{{ route('admin.blog_comments.destroy', $reply) }}" class="mt-1"
                                                      onsubmit="return confirm('Delete this reply?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link btn-sm p-0 text-danger">Delete reply</button>
                                                </form>
                                            </div>
                                        @endforeach
                                        <div class="collapse mt-2" id="reply-{{ $comment->id }}">
                                            <form method="POST" action="{{ route('admin.blog_comments.reply', $comment) }}">
                                                @csrf
                                                <textarea name="body" class="form-control mb-2" rows="3" required
                                                          maxlength="{{ \App\Models\BlogComment::MAX_LENGTH }}"
                                                          placeholder="Write a public reply…"></textarea>
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                                                    <i class="fe-send me-1"></i> Publish reply
                                                </button>
                                                @if($comment->status !== 'approved')
                                                    <small class="text-muted ms-2">Replying also approves this comment.</small>
                                                @endif
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        @if($comment->blog)
                                            <a class="bc-post" href="{{ route('blog.details', $comment->blog->slug) }}#comment-{{ $comment->id }}" target="_blank" rel="noopener">
                                                {{ Str::limit($comment->blog->title, 60) }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-pill {{ $badgeClass[$comment->status] ?? 'bg-secondary' }}">{{ ucfirst($comment->status) }}</span>
                                    </td>
                                    <td>
                                        <div class="bc-actions">
                                            @if($comment->status !== 'approved')
                                                <form method="POST" action="{{ route('admin.blog_comments.status', $comment) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill"><i class="fe-check me-1"></i>Approve</button>
                                                </form>
                                            @endif
                                            @if($comment->status !== 'spam')
                                                <form method="POST" action="{{ route('admin.blog_comments.status', $comment) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="spam">
                                                    <button type="submit" class="btn btn-warning btn-sm rounded-pill"><i class="fe-slash me-1"></i>Reject</button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-light btn-sm rounded-pill"
                                                    data-bs-toggle="collapse" data-bs-target="#reply-{{ $comment->id }}">
                                                <i class="fe-corner-up-left me-1"></i>Reply
                                            </button>
                                            <form method="POST" action="{{ route('admin.blog_comments.destroy', $comment) }}"
                                                  onsubmit="return confirm('Delete this comment and its replies?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill"><i class="fe-trash-2 me-1"></i>Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">No {{ $status === 'all' ? '' : $status }} comments.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($comments->hasPages())
                        <div class="mt-3">{{ $comments->links('pagination::bootstrap-5') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
