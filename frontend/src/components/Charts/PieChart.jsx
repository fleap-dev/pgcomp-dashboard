import React from 'react';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Pie } from 'react-chartjs-2';
import { useEffect, useState } from 'react';

ChartJS.register(ArcElement, Tooltip, Legend);

function PieChart({ filter }) {
    const options = {
        labels: {
            render: 'label'
        },
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: {
                display: true,
            },
            title: {
                display: false,
                text: 'Chart.js Pie Chart',
            },
        }
    }

    const data = {
        labels: ['CG', 'Análise de Dados', 'I.A',],
        datasets: [
            {
                label: '# of Votes',
                data: [12, 3, 5,],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                ],
                borderWidth: 1,
            },
        ],
    };


    useEffect(() => {
        console.log('Filtro atualizado: ' + filter);
    }, [filter]);

    return <Pie options={options} data={data} />;
}

export default PieChart