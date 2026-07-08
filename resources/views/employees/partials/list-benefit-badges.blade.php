<div class="emp-benefit-badges">
    @if($employee->pf)
        <span class="emp-benefit-chip emp-benefit-chip--yes"
            title="PF eligible{{ $employee->pf_number ? ' · ' . $employee->pf_number : '' }}">
            <i class="feather-check"></i> PF
        </span>
    @endif
    @if($employee->esi)
        <span class="emp-benefit-chip emp-benefit-chip--yes"
            title="ESI eligible{{ $employee->esi_number ? ' · ' . $employee->esi_number : '' }}">
            <i class="feather-check"></i> ESI
        </span>
    @endif
    @if($employee->insurance)
        <span class="emp-benefit-chip emp-benefit-chip--yes"
            title="Insurance enrolled{{ $employee->insurance_provider ? ' · ' . $employee->insurance_provider : '' }}">
            <i class="feather-check"></i> INS
        </span>
    @endif
    @if(!$employee->pf && !$employee->esi && !$employee->insurance)
        <span class="emp-benefit-empty">—</span>
    @endif
</div>
