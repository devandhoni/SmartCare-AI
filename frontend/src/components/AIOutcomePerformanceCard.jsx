function AIOutcomePerformanceCard({

    performance

}) {


    if(!performance)
    {

        return null;

    }



    return (

        <div className="rounded-xl bg-white p-6 shadow">


            <h2 className="text-xl font-bold text-slate-800">

                🤖 AI Outcome Performance

            </h2>



            <div className="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">



                <div className="rounded-lg bg-blue-50 p-4">

                    <p className="text-sm text-slate-500">

                        AI Accuracy

                    </p>


                    <p className="text-3xl font-bold text-blue-600">

                        {performance.average_ai_accuracy}%

                    </p>


                </div>





                <div className="rounded-lg bg-green-50 p-4">


                    <p className="text-sm text-slate-500">

                        Successful Intervention

                    </p>


                    <p className="text-3xl font-bold text-green-600">

                        {
                            performance.successful_interventions
                        }

                    </p>


                </div>







                <div className="rounded-lg bg-purple-50 p-4">


                    <p className="text-sm text-slate-500">

                        Success Rate

                    </p>


                    <p className="text-3xl font-bold text-purple-600">

                        {
                            performance.intervention_success_rate
                        }%

                    </p>


                </div>








                <div className="rounded-lg bg-orange-50 p-4">


                    <p className="text-sm text-slate-500">

                        Total Outcomes

                    </p>


                    <p className="text-3xl font-bold text-orange-600">

                        {
                            performance.total_outcomes_recorded
                        }

                    </p>


                </div>



            </div>






            <div className="mt-6">


                <h3 className="font-semibold text-slate-700">

                    Outcome Distribution

                </h3>



                <div className="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">



                    <div className="rounded-lg border p-3">

                        <p className="text-sm text-slate-500">

                            Improved

                        </p>

                        <p className="text-xl font-bold text-green-600">

                            {
                                performance
                                .outcome_distribution
                                .IMPROVED
                            }

                        </p>

                    </div>






                    <div className="rounded-lg border p-3">

                        <p className="text-sm text-slate-500">

                            Stable

                        </p>

                        <p className="text-xl font-bold text-blue-600">

                            {
                                performance
                                .outcome_distribution
                                .STABLE
                            }

                        </p>

                    </div>






                    <div className="rounded-lg border p-3">

                        <p className="text-sm text-slate-500">

                            Deteriorated

                        </p>

                        <p className="text-xl font-bold text-red-600">

                            {
                                performance
                                .outcome_distribution
                                .DETERIORATED
                            }

                        </p>

                    </div>







                    <div className="rounded-lg border p-3">

                        <p className="text-sm text-slate-500">

                            Unknown

                        </p>

                        <p className="text-xl font-bold text-yellow-600">

                            {
                                performance
                                .outcome_distribution
                                .UNKNOWN
                            }

                        </p>

                    </div>



                </div>


            </div>



        </div>

    );

}


export default AIOutcomePerformanceCard;