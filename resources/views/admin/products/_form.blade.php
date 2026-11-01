@csrf

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-white/80">Nom du produit <span class="text-amber-warm">*</span></label>
        <input id="name" type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required minlength="3" maxlength="100" class="mt-1 block w-full rounded-md {{ $errors->has('name') ? 'border-red-400' : 'border-gray-300 dark:border-white/10' }} bg-white shadow-sm focus:border-[#E3A23C] focus:ring-[#E3A23C]/30 dark:bg-[#1E3527] dark:text-white dark:placeholder:text-white/40">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-white/80">Description <span class="text-amber-warm">*</span></label>
        <textarea id="description" name="description" rows="4" required minlength="10" maxlength="1000" class="mt-1 block w-full rounded-md {{ $errors->has('description') ? 'border-red-400' : 'border-gray-300 dark:border-white/10' }} bg-white shadow-sm focus:border-[#E3A23C] focus:ring-[#E3A23C]/30 dark:bg-[#1E3527] dark:text-white dark:placeholder:text-white/40">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-white/80">Catégorie <span class="text-amber-warm">*</span></label>
        <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-md {{ $errors->has('category_id') ? 'border-red-400' : 'border-gray-300 dark:border-white/10' }} bg-white shadow-sm focus:border-[#E3A23C] focus:ring-[#E3A23C]/30 dark:bg-[#1E3527] dark:text-white">
            <option value="">Sélectionner une catégorie</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>{{ $category->name }}{{ ! $category->is_active ? ' · inactive' : '' }}</option>
            @endforeach
        </select>
        @if($categories->isEmpty())
            <p class="mt-1 text-xs text-ink/50 dark:text-white/50">Aucune catégorie active n'est disponible.</p>
        @endif
        @if(isset($product) && ! $product->category_id && $product->category)
            <p class="mt-1 text-xs text-ink/50 dark:text-white/50">Catégorie historique : {{ $product->category }}. Sélectionnez une catégorie pour associer le produit.</p>
        @endif
        @error('category_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="unit" class="block text-sm font-medium text-gray-700">Unité <span class="text-amber-warm">*</span></label>
        <input id="unit" type="text" name="unit" value="{{ old('unit', $product->unit ?? 'kg') }}" required minlength="1" maxlength="20" placeholder="kg, litre, pièce..." class="mt-1 block w-full rounded-md {{ $errors->has('unit') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="origin_country" class="block text-sm font-medium text-gray-700">Pays d'origine <span class="text-amber-warm">*</span></label>
        <input id="origin_country" type="text" name="origin_country" value="{{ old('origin_country', $product->origin_country ?? '') }}" required minlength="2" maxlength="60" class="mt-1 block w-full rounded-md {{ $errors->has('origin_country') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('origin_country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="origin_region" class="block text-sm font-medium text-gray-700">Région d'origine</label>
        <input id="origin_region" type="text" name="origin_region" value="{{ old('origin_region', $product->origin_region ?? '') }}" minlength="2" maxlength="80" class="mt-1 block w-full rounded-md {{ $errors->has('origin_region') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('origin_region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="producer_id" class="block text-sm font-medium text-gray-700">Producteur <span class="text-amber-warm">*</span></label>
        <select id="producer_id" name="producer_id" required class="mt-1 block w-full rounded-md {{ $errors->has('producer_id') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sélectionner un producteur</option>
            @foreach($producers as $producer)
                <option value="{{ $producer->id }}" @selected((string) old('producer_id', $product->producer_id ?? '') === (string) $producer->id)>{{ $producer->name }}</option>
            @endforeach
        </select>
        @error('producer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="barcode" class="block text-sm font-medium text-gray-700">Code-barres</label>
        <input id="barcode" type="text" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" minlength="8" maxlength="32" class="mt-1 block w-full rounded-md {{ $errors->has('barcode') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('barcode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-gray-700 dark:text-white/80">Image du produit</label>
        <input id="image" type="file" name="image" accept="image/*" class="mt-1 block w-full rounded-md {{ $errors->has('image') ? 'border-red-400' : 'border-gray-300 dark:border-white/10' }} bg-white text-sm text-ink shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-forest/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-forest hover:file:bg-forest/15 dark:bg-[#1E3527] dark:text-white">
        @error('image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-ink/50 dark:text-white/50">Formats acceptés: JPG, PNG, WEBP (max 5 Mo)</p>
        <img id="image-preview" @if(isset($product) && $product->image_url) src="{{ $product->image_url }}" @endif alt="Aperçu du produit" class="mt-3 {{ isset($product) && $product->image_url ? '' : 'hidden' }} max-h-[200px] max-w-full rounded-md border border-ink/20 object-contain">
        <p id="image-preview-error" class="mt-2 hidden text-sm text-ink/60">Impossible de charger l'image</p>
    </div>

    <div>
        <label for="carbon_footprint" class="block text-sm font-medium text-gray-700">Empreinte carbone <span class="text-amber-warm">*</span></label>
        <input id="carbon_footprint" type="number" step="0.01" min="0" max="9999" name="carbon_footprint" value="{{ old('carbon_footprint', $product->carbon_footprint ?? '') }}" required class="mt-1 block w-full rounded-md {{ $errors->has('carbon_footprint') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('carbon_footprint')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="verification_status" class="block text-sm font-medium text-gray-700">Statut de vérification <span class="text-amber-warm">*</span></label>
        <select id="verification_status" name="verification_status" required class="mt-1 block w-full rounded-md {{ $errors->has('verification_status') ? 'border-red-400' : 'border-gray-300' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach(['pending' => 'En attente', 'verified' => 'Vérifié', 'rejected' => 'Rejeté'] as $value => $label)
                <option value="{{ $value }}" @selected(old('verification_status', $product->verification_status ?? 'pending') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('verification_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-center gap-3 md:col-span-2">
        <input type="checkbox" name="is_organic" value="1" @checked(old('is_organic', $product->is_organic ?? false)) class="rounded {{ $errors->has('is_organic') ? 'border-red-400' : 'border-gray-300' }} text-indigo-600 shadow-sm focus:ring-indigo-500">
        <span class="text-sm font-medium text-gray-700">Produit biologique</span>
        @error('is_organic')<span class="text-sm text-red-600">{{ $message }}</span>@enderror
    </label>
</div>

<div class="mt-8 flex items-center justify-end gap-4">
    <a href="{{ route('admin.products.index') }}" class="text-ink/65 hover:text-forest">Annuler</a>
    <button type="submit" class="rounded bg-amber-500 px-4 py-2 font-bold text-white hover:bg-amber-600">{{ $submitLabel }}</button>
</div>

<script>
    (() => {
        const form = document.currentScript.closest('form');
        const requiredFields = [...form.querySelectorAll('[required]')];
        const imageInput = form.querySelector('#image');
        const imagePreview = form.querySelector('#image-preview');
        const imagePreviewError = form.querySelector('#image-preview-error');
        const initialImagePreview = imagePreview.getAttribute('src');
        let previewObjectUrl = null;

        form.noValidate = true;

        form.addEventListener('submit', (event) => {
            let firstInvalidField = null;

            requiredFields.forEach((field) => {
                const errorContainer = field.parentElement;
                let errorMessage = errorContainer.querySelector('p.text-sm.text-red-600');

                if (field.value.trim() === '') {
                    if (!errorMessage) {
                        errorMessage = document.createElement('p');
                        errorMessage.className = 'mt-1 text-sm text-red-600';
                        errorMessage.dataset.clientRequiredError = 'true';
                        field.insertAdjacentElement('afterend', errorMessage);
                    } else if (!errorMessage.dataset.clientRequiredError) {
                        errorMessage.dataset.originalMessage = errorMessage.textContent;
                        errorMessage.dataset.clientRequiredError = 'true';
                    }

                    errorMessage.textContent = 'Ce champ est obligatoire.';
                    field.classList.add('border-red-400');
                    firstInvalidField ??= field;
                    return;
                }

                if (errorMessage?.dataset.clientRequiredError === 'true') {
                    if (errorMessage.dataset.originalMessage !== undefined) {
                        errorMessage.textContent = errorMessage.dataset.originalMessage;
                    } else {
                        errorMessage.remove();
                        field.classList.remove('border-red-400');
                    }
                }
            });

            if (firstInvalidField) {
                event.preventDefault();
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        const updateImagePreview = () => {
            imagePreviewError.classList.add('hidden');

            if (previewObjectUrl) {
                URL.revokeObjectURL(previewObjectUrl);
                previewObjectUrl = null;
            }

            const imageFile = imageInput.files?.[0];
            if (!imageFile) {
                if (initialImagePreview) {
                    imagePreview.src = initialImagePreview;
                    imagePreview.classList.remove('hidden');
                } else {
                    imagePreview.removeAttribute('src');
                    imagePreview.classList.add('hidden');
                }
                return;
            }

            previewObjectUrl = URL.createObjectURL(imageFile);
            imagePreview.src = previewObjectUrl;
            imagePreview.classList.remove('hidden');
        };

        imageInput.addEventListener('change', updateImagePreview);
        imagePreview.addEventListener('error', () => {
            imagePreview.classList.add('hidden');
            imagePreviewError.classList.remove('hidden');
        });

        window.addEventListener('pagehide', () => {
            if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        });

        updateImagePreview();
    })();
</script>
