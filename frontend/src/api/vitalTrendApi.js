import axios from "axios";


const API_URL = "http://127.0.0.1:8000/api";



export const getVitalTrend = async(
    residentId,
    period = "today",
    metric = "all"
)=>{


    const token = localStorage.getItem("token");



    const response = await axios.get(

        `${API_URL}/residents/${residentId}/vital-trends`,

        {

            params:{

                period: period,

                metric: metric

            },


            headers:{

                Authorization:`Bearer ${token}`

            }


        }

    );



    return response.data;


};