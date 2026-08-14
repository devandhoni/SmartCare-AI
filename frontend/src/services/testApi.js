import api from "./api";


export const testBackend = async()=>{


    try{


        const response =
        await api.get("/dashboard/ai-command-center");


        console.log(response.data);


    }

    catch(error)
    {


        console.error(error);


    }


};