import Toolbar from "./components/Toolbar/Toolbar";
import styles from "./App.module.css"
import Title from "./components/Title/Title";
import DataCard from "./components/DataCard/DataCard";
import StudentsPerTeacherChart from "./components/Charts/StudentsPerTeacherChart";
import AssessmentIcon from "@mui/icons-material/Assessment";
import PieChartIcon from '@mui/icons-material/PieChart';
import Footer from "./components/Footer/Footer";
import QualisChart from "./components/Charts/QualisChart";
import ProductionPerStudentChart from "./components/Charts/ProductionPerStudentChart";
import ProductionsAmountChart from "./components/Charts/ProductionsAmountChart";
import Utils from './Utils'
import StudentsPerSubfieldChart from "./components/Charts/StudentsPerSubfieldChart";
import StudentsPerFieldChart from "./components/Charts/StudentsPerFieldChart";

export function App() {
    return (
        <div className={styles.app__global}>

            <Toolbar />

            <Title />

            <div className={styles.cards__container}>
                <DataCard title="Quantidade de produções científicas"
                    minWidth="1000px"
                    minHeight="300px"
                    icon={AssessmentIcon}
                    filterOptions={Utils.universityFilter}
                    chart={ProductionsAmountChart} />

                <DataCard title="Qualis"
                    minWidth="1000px"
                    minHeight="300px"
                    icon={AssessmentIcon}
                    filterOptions={Utils.universityFilter}
                    chart={QualisChart} />

                <DataCard title="Produção por discentes"
                    minWidth="1000px"
                    minHeight="300px"
                    icon={AssessmentIcon}
                    filterOptions={Utils.universityFilter}
                    chart={ProductionPerStudentChart} />

                <DataCard title="Alunos por docente"
                    minWidth="1200px"
                    minHeight="400px"
                    icon={AssessmentIcon}
                    filterOptions={Utils.universityAndActivesFilter}
                    chart={StudentsPerTeacherChart} />

                <DataCard title="Alunos por área"
                    minWidth="1000px"
                    minHeight="350px"
                    height="250px"
                    type="fields"
                    icon={PieChartIcon}
                    filterOptions={Utils.universityAndActivesFilter}
                    chart={StudentsPerFieldChart} />

                <DataCard title="Alunos por subárea"
                    minWidth="1000px"
                    minHeight="350px"
                    height="250px"
                    type="subfields"
                    icon={PieChartIcon}
                    filterOptions={Utils.universityAndActivesFilter}
                    chart={StudentsPerSubfieldChart} />
            </div>

            <Footer />
        </div>
    )
}