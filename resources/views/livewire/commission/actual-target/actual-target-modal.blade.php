<div class="modal fade modal-lg" id="modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Form Target Aktual Atap</h1>
                <button type="button" class="btn-close" wire:click="closeModal()"></button>
            </div>
            <div class = "modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="form-label">Tipe <span class="text-danger">*</span></div>
                        <select class="form-select @error('category') is-invalid @enderror" id="status" wire:model="category" aria-label="Default select example">
                            <option value=""selected style="display: none">-- Pilih Tipe --</option>
                            @foreach ($categories as $data_category)
                                <option value="{{ $data_category }}" {{ $category == $data_category ? "selected" : "" }}>{{ Str::title(Str::replace('-', ' ', $data_category)) }}</option>
                            @endforeach
                            {{-- <option value="dr-sonne" {{ $category == 'dr-sonne' ? "selected" : "" }}>Dr Sonne</option> --}}
                        </select>
                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Nominal Target <span class="text-danger">*</span></div>
                        <input type="number" class="form-control @error('target') is-invalid @enderror" wire:model="target" placeholder="Contoh : 100000000" min="0">
                        @error('target')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Aktual Persentase <span class="text-danger">*</span></div>
                        <input type="number" class="form-control @error('actual') is-invalid @enderror" wire:model="actual" placeholder="Contoh : 60" min="0">
                        @error('actual')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-6">
                        <div class="form-label">Nominal Komisi <span class="text-danger">*</span></div>
                        <input type="number" class="form-control @error('value_commission') is-invalid @enderror" wire:model="value_commission" placeholder="Contoh : 60" min="0">
                        @error('value_commission')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal()">Tutup <i class="fa-solid fa-circle-xmark fa-fw ms-2"></i></button>
                <button type="button" class="btn btn-success btn-sm" wire:click="saveData()">Simpan <i class="fa-solid fa-circle-check fa-fw ms-2"></i></button>
            </div>
        </div>
    </div>
</div>
