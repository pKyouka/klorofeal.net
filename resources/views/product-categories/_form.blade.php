@csrf

<div class="grid grid-cols-1 gap-4">
    <div>
        <label for="name" class="mb-1 block text-sm font-semibold text-slate-700">Category Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $category->name ?? '') }}" required class="w-full px-3 py-2 text-sm">
        @error('name')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
        <textarea id="description" name="description" rows="4" class="w-full px-3 py-2 text-sm">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Save</button>
    <a href="{{ route('product-categories.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
