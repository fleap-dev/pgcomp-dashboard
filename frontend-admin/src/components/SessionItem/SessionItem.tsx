import styles from './SessionItem.module.css';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import { Collapse, ListItem } from '@mui/material';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';
import { useState } from 'react';
import Utils from '../../Utils'
import SessionItemDialog from '../SessionItemDialog/SessionItemDialog';
import React from 'react';

interface SessionItemProps {
    name?: string,
    area_name?: string,
    type: string,
    children?: any,
    isChildren?: boolean
}

interface namePropertyProps {
    areas: string
}

function SessionItem(props: any) {
    const iconsStyle = {
        height: '30px',
        width: '35px',
        cursor: 'pointer'
    }

    const nameProperty: any = {
        'areas': 'area_name'
    }

    const [expandChildren, setExpandChildren] = useState(false);

    const [modalOpened, setModalOpened] = useState(false);

    const handleModalOpen = () => {
        setModalOpened(true);
    }

    const handleModalClose = () => {
        setModalOpened(false);
    }

    const childrenStyle = props.isChildren ? { marginLeft: '15px' } : {};

    return (
        <>
            <ListItem disablePadding style={childrenStyle}>
                <div className={styles['SessionItem']}>
                    <div>{props[ nameProperty[props.type] ]}</div>
                    <div>
                        <EditIcon style={iconsStyle} onClick={() => setModalOpened(true)} />
                        <DeleteIcon style={iconsStyle} />
                        {props.isChildren ? null : expandChildren ? <ExpandLessIcon style={iconsStyle} onClick={() => setExpandChildren(!expandChildren)} /> :
                            <ExpandMoreIcon style={iconsStyle} onClick={() => setExpandChildren(!expandChildren)} />}
                    </div>
                </div>
            </ListItem>

            <SessionItemDialog type={Utils.nameTypes[props.type]} typeAttr={props.type} open={modalOpened} handleClose={handleModalClose}
                isEdit={true} name={props[ nameProperty[props.type] ]} />

            <Collapse in={expandChildren} timeout="auto" unmountOnExit>
                {props.children ? props.children.map((item: SessionItemProps) => {
                    return <SessionItem name={item.name} type={item.type} isChildren={true} />
                }) : null}
            </Collapse>


        </>
    )
}

export default SessionItem;