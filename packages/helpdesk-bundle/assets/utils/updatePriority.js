import {updateTicketStatutService} from "@requests";

const updatePriority = async (id,newPriority) => {
    try{
        const data={priority:newPriority}
        await updateTicketStatutService(id, data, true);
    }
    catch (error){
        console.error('Erreur lors de la mise à jour de la priorité',error);
        await getTickets();
    }
}

export { updatePriority};