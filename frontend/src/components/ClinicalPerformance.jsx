function ClinicalPerformance({
    performance
}) {


    const escalation =
        performance.escalation_metrics;


    const nursing =
        performance.nursing_metrics;



    return (

        <div className="rounded-xl bg-white p-6 shadow">


            <h2 className="text-xl font-bold text-slate-800">

                Clinical Performance Analytics

            </h2>





            <div className="mt-6 grid grid-cols-1 gap-5 md:grid-cols-5">





                <div className="rounded-lg bg-red-50 p-4">


                    <p className="text-sm text-slate-500">

                        Total Escalations

                    </p>


                    <p className="mt-2 text-3xl font-bold text-red-600">

                        {
                            escalation.total_escalations
                        }

                    </p>


                </div>







                <div className="rounded-lg bg-blue-50 p-4">


                    <p className="text-sm text-slate-500">

                        Response Time

                    </p>


                    <p className="mt-2 text-3xl font-bold text-blue-600">

                        {
                            escalation.average_response_time_minutes
                        }

                        <span className="text-lg">

                            min

                        </span>

                    </p>


                </div>








                <div className="rounded-lg bg-green-50 p-4">


                    <p className="text-sm text-slate-500">

                        SLA Compliance

                    </p>


                    <p className="mt-2 text-3xl font-bold text-green-600">

                        {
                            escalation.sla_compliance_percentage
                        }%

                    </p>


                </div>








                <div className="rounded-lg bg-orange-50 p-4">


                    <p className="text-sm text-slate-500">

                        Pending Tasks

                    </p>


                    <p className="mt-2 text-3xl font-bold text-orange-600">

                        {
                            nursing.pending_tasks
                        }

                    </p>


                </div>








                <div className="rounded-lg bg-purple-50 p-4">


                    <p className="text-sm text-slate-500">

                        Completed Tasks

                    </p>


                    <p className="mt-2 text-3xl font-bold text-purple-600">

                        {
                            nursing.completed_tasks
                        }

                    </p>


                </div>





            </div>


        </div>

    );


}


export default ClinicalPerformance;