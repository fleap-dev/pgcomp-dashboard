import styles from './Footer.module.css'
import LinkedInIcon from '@mui/icons-material/LinkedIn';
import FacebookIcon from '@mui/icons-material/Facebook';
import TwitterIcon from '@mui/icons-material/Twitter';

function Footer() {
    return (
        <div className={styles.footer__ufba}>
            <div className={styles.footer__left}>
                <h2>Universidade Federal da Bahia</h2>
                <h5>Avenida Milton Santos, s\n - Campus de Ondina, PAF 2 <br /> CEP: 40.170-110 Salvador-Bahia </h5>
                <h3>Site Oficial  |  Contato  |  Sobre a UFBA</h3>
            </div>

            <div className={styles.footer__right}>
                <a href='https://www.linkedin.com/school/ufba/?originalSubdomain=br'><LinkedInIcon  color="white" fontSize='large' /> </a>
                <a href='https://pt-br.facebook.com/pages/category/Specialty-School/Universidade-Federal-da-Bahia-UFBA-231509166876211/'><FacebookIcon color="white" fontSize='large' /> </a>
                <a href='https://twitter.com/ufba?lang=en'><TwitterIcon color="white" fontSize='large' />  </a>
            </div>

        </div>
    )
}

export default Footer