export const paymentPlanRates = {
    acelerar: {
        2: 6.53,
        3: 8.35,
        4: 10.15,
        5: 11.87,
        6: 13.01,
        7: 14.11,
        8: 15.21,
        9: 16.28,
        10: 17.32,
        11: 18.36,
        12: 19.37,
        13: 20.58,
        14: 21.79,
        15: 23.0,
        16: 24.21,
        17: 25.42,
        18: 26.63,
    },
    turbo: {
        2: 5.63,
        3: 7.2,
        4: 8.75,
        5: 10.23,
        6: 11.21,
        7: 12.17,
        8: 13.11,
        9: 14.03,
        10: 14.93,
        11: 15.83,
        12: 16.7,
        13: 17.71,
        14: 18.73,
        15: 19.74,
        16: 20.76,
        17: 21.77,
        18: 22.79,
    },
    economico: {
        2: 3.75,
        3: 3.75,
        4: 3.75,
        5: 3.75,
        6: 3.75,
        7: 3.75,
        8: 3.75,
        9: 3.75,
        10: 3.75,
        11: 3.75,
        12: 3.75,
        13: 5.98,
        14: 5.98,
        15: 5.98,
        16: 5.98,
        17: 5.98,
        18: 5.98,
    },
};

export const paymentPlans = [
    {
        label: 'Plano Acelerar',
        value: 'acelerar',
        description: 'Mais indicado para antecipação e volume de parcelado.',
    },
    {
        label: 'Plano Turbo',
        value: 'turbo',
        description: 'Equilíbrio entre taxa e velocidade de recebimento.',
    },
    {
        label: 'Plano Econômico',
        value: 'economico',
        description: 'Taxa reduzida para quem prioriza custo.',
    },
];

export const installmentOptions = Array.from({ length: 17 }, (_, index) => {
    const installments = index + 2;

    return {
        label: `${installments}x`,
        value: String(installments),
    };
});

export function getPaymentPlanByValue(planValue) {
    return paymentPlans.find((plan) => plan.value === planValue) ?? paymentPlans[0];
}

