@extends('admin.layout')

@section('title', 'Edit Credential')

@section('content')
<div class="p-8">
    <h1 class="text-4xl font-bold text-slate-900 mb-8">Edit Credential</h1>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <form action="{{ route('admin.credentials.update', $credential->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-slate-900 mb-2">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $credential->title) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                @error('title')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-slate-900 mb-2">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $credential->description) }}</textarea>
                @error('description')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Expires At -->
            <div class="mb-6">
                <label for="expires_at" class="block text-sm font-medium text-slate-900 mb-2">Expiration Date</label>
                <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at', $credential->expires_at?->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                @error('expires_at')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Review Notes -->
            <div class="mb-6">
                <label for="review_notes" class="block text-sm font-medium text-slate-900 mb-2">Review Notes</label>
                <textarea id="review_notes" name="review_notes" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('review_notes', $credential->review_notes) }}</textarea>
                @error('review_notes')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    Save Changes
                </button>
                <a href="{{ route('admin.credentials.index') }}" class="bg-slate-300 hover:bg-slate-400 text-slate-900 px-6 py-2 rounded-lg font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
