@csrf

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1 block text-sm font-semibold text-slate-700">Supplier Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $supplier->name ?? '') }}" required class="w-full px-3 py-2 text-sm">
        @error('name')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact_person" class="mb-1 block text-sm font-semibold text-slate-700">Contact Person</label>
        <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="w-full px-3 py-2 text-sm">
        @error('contact_person')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="mb-1 block text-sm font-semibold text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $supplier->phone ?? '') }}" class="w-full px-3 py-2 text-sm">
        @error('phone')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $supplier->email ?? '') }}" class="w-full px-3 py-2 text-sm">
        @error('email')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="address" class="mb-1 block text-sm font-semibold text-slate-700">Address</label>
        <textarea id="address" name="address" rows="4" class="w-full px-3 py-2 text-sm">{{ old('address', $supplier->address ?? '') }}</textarea>
        @error('address')
            <p class="mt-1 text-xs font-semibold text-orange-700">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="inline-flex items-center rounded-xl border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">Save</button>
    <a href="{{ route('suppliers.index') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
