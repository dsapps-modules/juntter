export const installmentOptions = Array.from({ length: 17 }, (_, index) => {
    const installments = index + 2;

    return {
        label: `${installments}x`,
        value: String(installments),
    };
});

export function normalizeFlags(flags) {
    if (!Array.isArray(flags)) {
        return [];
    }

    return flags.filter((flag) => {
        if (!flag || typeof flag !== 'object') {
            return false;
        }

        return String(flag.name ?? '').toUpperCase() !== 'BACEN';
    });
}

export function formatFlagLabel(flag) {
    const flagName = String(flag?.name ?? '').trim();

    if (flagName.toUpperCase() === 'OTHERS') {
        return 'Outros';
    }

    return `${flagName || `Bandeira ${flag?.id ?? ''}`}`.trim();
}

export function buildFlagOptions(flags) {
    return normalizeFlags(flags).map((flag) => ({
        label: formatFlagLabel(flag),
        value: String(flag.id ?? flag.name ?? ''),
    }));
}

export function buildInstallmentOptions(flag) {
    const creditFees = flag?.fees?.credit;

    if (!creditFees || typeof creditFees !== 'object') {
        return [];
    }

    return Object.entries(creditFees)
        .map(([key, value]) => ({
            key,
            amount: Number(value),
        }))
        .filter((item) => /^(\d+)x$/.test(item.key) && Number.isFinite(item.amount))
        .sort((left, right) => Number.parseInt(left.key, 10) - Number.parseInt(right.key, 10))
        .map((item) => ({
            label: item.key,
            value: item.key,
        }));
}

export function resolveSelectedFlag(flags, selectedFlagId) {
    if (!Array.isArray(flags) || flags.length === 0) {
        return null;
    }

    if (selectedFlagId !== null) {
        const matchedFlag = flags.find((flag) => String(flag.id ?? flag.name ?? '') === selectedFlagId);

        if (matchedFlag) {
            return matchedFlag;
        }
    }

    return flags.find((flag) => flag.active) ?? flags[0] ?? null;
}

export function resolveRate(flag, installmentValue) {
    if (!flag) {
        return 0;
    }

    const creditFees = flag?.fees?.credit ?? {};

    return Number(creditFees[installmentValue] ?? 0);
}
