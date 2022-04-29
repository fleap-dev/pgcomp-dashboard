import { Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import axios from 'axios';
import { map } from 'lodash';
import { useEffect, useState } from 'react';
import generateColorsArray from '../../Utils.js'
//TODO: get na url 'dashboard/production_per_qualis'

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

const generateValues = (numberOfValues) => {
    const values = [];
    for (let i = 0; i < numberOfValues; i++) {
        values.push(Math.floor(Math.random() * 150) + 1);
    }

    return values;
}

function QualisChart({ filter }) {
    const [chartData, setChartData] = useState(null);
    const NUMBER_OF_ITEMS = 19;

    const qualisCategoriesColors = {
        'A1': '#7CBB00',
        'A2': '#FF6C6C',
        'A3': '#3098DC',
        'A4': '#868686',
        'B1': '#E76A05',
        'B2': '#F25AFF',
        'B3': '#5A938F',
        'B4': '#BBB400'
    }

    const options = {
        elements: {
            bar: {
                borderWidth: 1,
            },
        },
        responsive: true,
        scales: {
            x: {
                stacked: true,
            },
            y: {
                stacked: true,
            },
        },
        plugins: {
            title: {
                display: false
            },
        }
    }

    const getData = (selectedFilter = []) => {
        axios.get('http://localhost:8000/api/dashboard/production_per_qualis', { params: { selectedFilter } })
            .then(({ data, year }) => {
                const labels = data.years;
                const dataChart = data
                    .data
                    .filter((qualis) => {
                        return qualis.label !== '-'
                    })
                    .map((qualis) => {
                        return {
                            ...qualis,
                            backgroundColor: qualisCategoriesColors[qualis.label]

                        }

                    });

                const qualisData = {
                    labels,
                    datasets: dataChart
                };

                setChartData(qualisData);

                console.log('qualis: ', qualisData);
            });
    }

    useEffect(() => {        
        getData();
    }, []);

    useEffect(() => {
        console.log('Filtro atualizado: ' + filter);
    }, [filter]);

    return (
        chartData ? <Bar options={options} data={chartData} /> : null

    )
}

export default QualisChart
