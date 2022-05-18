import styles from './UserMenu.module.css';
import AccountCircleIcon from '@mui/icons-material/AccountCircle';
import FormControl from '@mui/material/FormControl';
import Select, { SelectChangeEvent } from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import { Button, Menu } from '@mui/material';
import React, { useContext, useEffect, useState } from 'react';
import { AuthContext } from '../../providers/AuthProvider';
import axios from 'axios';
function UserMenu() {
    const iconStyle = {
        'height': '42px',
        'width': '42px'
    }

    const [anchorEl, setAnchorEl] = React.useState<null | HTMLElement>(null);
    const open = Boolean(anchorEl);
    const {isLogged, token} = useContext(AuthContext)
    const [userName, setUsername] = useState();

    useEffect(() => {
        if(isLogged){
            axios.get('https://mate85-api.litiano.dev.br/api/user', {headers: {'Authorization': `${token}`}}).then(response=> {
                setUsername(response.data.name);
            })
        }
    }, [isLogged, token])

    const handleClick = (event: React.MouseEvent<HTMLButtonElement>) => {
        setAnchorEl(event.currentTarget);
    };

    const handleClose = () => {
        setAnchorEl(null);
    };

    return (
        <div className={styles['UserMenu']}>
            <Button
                onClick={handleClick}
                aria-controls={open ? 'basic-menu' : undefined}
                aria-haspopup="true"
                aria-expanded={open ? 'true' : undefined}>
                <AccountCircleIcon color="primary" fontSize='inherit' style={iconStyle} />

                {!isLogged ? 
                    <div><a href="/">Entrar</a></div> 
                    : 
                    <div className={styles['user__menu__welcome']}>
                        <span>Olá,</span>
                        <span className={styles['user__menu__name']}>{userName}</span>
                    </div>
                }
                
            </Button>

            <Menu id="user-menu"
                anchorEl={anchorEl}
                open={open}
                onClose={handleClose}
                MenuListProps={{
                    'aria-labelledby': 'basic-button',
                }}>
                <MenuItem onClick={handleClose}>Sair</MenuItem>
            </Menu>
        </div>
    )
}

export default UserMenu
