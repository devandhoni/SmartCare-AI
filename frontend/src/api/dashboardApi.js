import api from "../services/api";





export const getAICommandCenter = async()=>{


    const response =
    await api.get(
        "/dashboard/ai-command-center"
    );


    return response.data;


};









export const getAIAlerts = async()=>{


    const response =
    await api.get(
        "/ai-alerts"
    );


    return response.data;


};









export const getNurseTasks = async()=>{


    const response =
    await api.get(
        "/nurse/tasks"
    );


    return response.data;


};