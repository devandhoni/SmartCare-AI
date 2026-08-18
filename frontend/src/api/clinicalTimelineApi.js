import axios from "axios";


const API_URL =
    "http://127.0.0.1:8000/api";



export const getClinicalTimeline = async (residentId) => {

    const response = await axios.get(
        `${API_URL}/residents/${residentId}/timeline`
    );


    return response.data;

};