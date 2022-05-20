import { List } from '@mui/material';
import { useState, useEffect } from 'react';
import AddSessionItemButton from '../AddSessionItemButton/AddSessionItemButton'
import SessionItemDialog from '../SessionItemDialog/SessionItemDialog';
import SessionItem from '../SessionItem/SessionItem';
import styles from './Session.module.css'
import Utils from '../../Utils'
import React from 'react';
import { api } from '../../services/api';

interface SessionProps {
    type: string;
}

function Session(props: SessionProps) {
    const [modalOpened, setModalOpened] = useState(false);

    const [sessionItems, setSessionItems] = useState([]);

    const handleModalOpen = () => {
        setModalOpened(true);
    }

    const handleModalClose = () => {
        setModalOpened(false);
    }

    const mockedChilds = [
        { name: 'child 1', type: 'qualis' },
        { name: 'child 2', type: 'qualis' },
    ]

    const getData = () => {
            api.get(props.type).then((response: any) => {
                if (response && response.status === 200 && response.data.data){
                    setSessionItems(response.data.data);
                }
            });
    }

    useEffect(() => {
        getData();
    }, [props.type]);

    console.log(sessionItems);

    return (
        <div className={styles['Session']}>
            <AddSessionItemButton type={Utils.nameTypes[props.type]} handleOpen={handleModalOpen} />
            <List disablePadding>
            { sessionItems && sessionItems.length ? 
                sessionItems.map((sessionItem: any) => {
                    return <SessionItem {...sessionItem} type={props.type} children={mockedChilds} key={sessionItem.id} />
                }) : null }
            </List>

            <SessionItemDialog type={Utils.nameTypes[props.type]} typeAttr={props.type} open={modalOpened} handleClose={handleModalClose} />
        </div>
    )
}

export default Session
