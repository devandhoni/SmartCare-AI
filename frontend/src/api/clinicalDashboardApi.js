import axios from "axios";


const API_URL = "http://127.0.0.1:8000/api";


export const getClinicalDashboard = async(residentId)=>{


const token = localStorage.getItem("token");


const response = await axios.get(
`${API_URL}/residents/${residentId}/clinical-dashboard`,
{
headers:{
Authorization:`Bearer ${token}`
}
}
);


return response.data;


};      