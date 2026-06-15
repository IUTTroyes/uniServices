import {updateTicketStatutService} from "@requests";

const updateAssigne = async (id,personnelIri) => {
    try{
        const data = {
            assigne:personnelIri
        }
        await updateTicketStatutService(id, data, true);
        console.log(data)
    }
    catch (error){
        console.error('Erreur lors de la mise à jour du personnel assigné',error);
    }
}

export { updateAssigne};