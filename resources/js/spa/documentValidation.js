export function normalizeDigits(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

export function formatCpf(value) {
    const digits = normalizeDigits(value).slice(0, 11);

    if (!digits) {
        return '';
    }

    if (digits.length <= 3) {
        return digits;
    }

    if (digits.length <= 6) {
        return `${digits.slice(0, 3)}.${digits.slice(3)}`;
    }

    if (digits.length <= 9) {
        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
    }

    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
}

export function formatCnpj(value) {
    const digits = normalizeDigits(value).slice(0, 14);

    if (!digits) {
        return '';
    }

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 5) {
        return `${digits.slice(0, 2)}.${digits.slice(2)}`;
    }

    if (digits.length <= 8) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
    }

    if (digits.length <= 12) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
    }

    return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
}

export function isValidCpf(value) {
    const cpf = normalizeDigits(value);

    if (cpf.length !== 11) {
        return false;
    }

    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    for (let digitPosition = 9; digitPosition < 11; digitPosition += 1) {
        let sum = 0;

        for (let index = 0; index < digitPosition; index += 1) {
            sum += Number(cpf[index]) * ((digitPosition + 1) - index);
        }

        const calculatedDigit = ((sum * 10) % 11) % 10;

        if (Number(cpf[digitPosition]) !== calculatedDigit) {
            return false;
        }
    }

    return true;
}

export function isValidCnpj(value) {
    const cnpj = normalizeDigits(value);

    if (cnpj.length !== 14) {
        return false;
    }

    if (/^(\d)\1{13}$/.test(cnpj)) {
        return false;
    }

    const digits = cnpj.split('').map((digit) => Number(digit));
    const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    let sum = 0;
    for (let index = 0; index < 12; index += 1) {
        sum += digits[index] * weights1[index];
    }

    let remainder = sum % 11;
    let firstDigit = remainder < 2 ? 0 : 11 - remainder;

    if (digits[12] !== firstDigit) {
        return false;
    }

    sum = 0;
    for (let index = 0; index < 13; index += 1) {
        sum += digits[index] * weights2[index];
    }

    remainder = sum % 11;
    const secondDigit = remainder < 2 ? 0 : 11 - remainder;

    return digits[13] === secondDigit;
}

export function isValidDocument(value) {
    const digits = normalizeDigits(value);

    if (digits.length === 11) {
        return isValidCpf(digits);
    }

    if (digits.length === 14) {
        return isValidCnpj(digits);
    }

    return false;
}

export function getDocumentType(value) {
    const digits = normalizeDigits(value);

    if (digits.length > 11) {
        return 'cnpj';
    }

    return 'cpf';
}

export function formatDocument(value) {
    return getDocumentType(value) === 'cnpj' ? formatCnpj(value) : formatCpf(value);
}
