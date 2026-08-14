import api from "../services/api";



export const getNurseTasks = async()=>{


    const response =
        await api.get(
            "/nurse/tasks"
        );


    return response.data;


};





export const assignNurseTask = async(
    id,
    assigned_to
)=>{


    const response =
        await api.put(

            `/nurse/tasks/${id}/assign`,

            {
                assigned_to
            }

        );


    return response.data;


};





export const acceptNurseTask = async(
    id
)=>{


    const response =
        await api.put(

            `/nurse/tasks/${id}/accept`

        );


    return response.data;


};





export const completeNurseTask = async(
    id
)=>{


    const response =
        await api.put(

            `/nurse/tasks/${id}/complete`

        );


    return response.data;


};