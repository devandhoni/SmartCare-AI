import api from "../services/api";


export const acknowledgeAlert = async (id) => {

    const response =
        await api.put(
            `/ai-alerts/${id}/acknowledge`
        );

    return response.data;

};



export const resolveAlert = async (
    id,
    resolutionNote
) => {

    const response =
        await api.put(
            `/ai-alerts/${id}/resolve`,
            {
                resolution_note: resolutionNote
            }
        );

    return response.data;

};