import { useState } from "react";

import {
    acceptNurseTask,
    completeNurseTask
} from "../api/nurseTaskApi";


import {
    useNavigate
} from "react-router-dom";





function NurseTaskTable({

    tasks = [],

    onRefresh

}) {



    const [processingId,setProcessingId] =
        useState(null);



    const navigate = useNavigate();








    const handleAccept = async(id)=>{


        try{


            setProcessingId(id);


            await acceptNurseTask(id);


            await onRefresh();


        }

        catch(error){


            console.error(
                "Accept task error:",
                error
            );


        }

        finally{


            setProcessingId(null);


        }


    };









    const handleComplete = async(id)=>{


        try{


            setProcessingId(id);


            await completeNurseTask(id);


            await onRefresh();


        }

        catch(error){


            console.error(
                "Complete task error:",
                error
            );


        }

        finally{


            setProcessingId(null);


        }


    };









    const handleViewClinical = (task)=>{


        if(task.resident_id)
        {


            navigate(

                `/residents/${task.resident_id}/clinical-dashboard`

            );


        }


    };









    const getSeverityStyle = (severity)=>{


        switch(severity)
        {


            case "CRITICAL":

                return (
                    "bg-red-600 text-white"
                );


            case "WARNING":

                return (
                    "bg-orange-100 text-orange-700"
                );


            default:

                return (
                    "bg-blue-100 text-blue-700"
                );


        }


    };











return(



<div className="
rounded-xl
bg-white
p-6
shadow
">





<div className="
flex
justify-between
items-center
mb-5
">


<h2 className="
text-xl
font-bold
text-slate-800
">


🩺 AI Nursing Work Queue


</h2>





<span className="
rounded-full
bg-blue-100
px-3
py-1
text-sm
font-bold
text-blue-700
">


{
tasks.length
}

Tasks


</span>


</div>









{
tasks.length === 0


?

(

<p className="
text-slate-500
">

No pending nurse tasks.

</p>


)



:


(



<div className="
space-y-5
">






{

tasks.map(task=>(



<div

key={task.id}

className="
border
rounded-xl
p-5
hover:shadow-md
transition
"



>







<div className="
flex
justify-between
items-start
"

>





<div>


<h3 className="
text-lg
font-bold
text-slate-800
">


{
task.resident?.full_name
??
"Unknown Resident"
}


</h3>




<p className="
text-sm
text-slate-500
mt-1
">

Task:

{" "}

{
task.task_name
??
"AI Clinical Intervention"
}


</p>


</div>








{

task.alert &&



<span

className={`
rounded-full
px-3
py-1
text-xs
font-bold

${

getSeverityStyle(
task.alert.severity
)

}

`}


>


{
task.alert.severity
}


</span>


}





</div>









<div className="
grid
grid-cols-1
md:grid-cols-3
gap-4
mt-5
">







<div className="
bg-slate-50
rounded-lg
p-4
">


<p className="
text-sm
text-gray-500
">

Status

</p>



<p className="
font-bold
mt-1
">

{
task.status
}


</p>


</div>








<div className="
bg-purple-50
rounded-lg
p-4
">


<p className="
text-sm
text-gray-500
">

AI Confidence

</p>



<p className="
font-bold
text-purple-700
mt-1
">


{

task.alert?.ai_confidence
??

"-"

}%

</p>


</div>








<div className="
bg-blue-50
rounded-lg
p-4
">


<p className="
text-sm
text-gray-500
">

Assigned Nurse

</p>



<p className="
font-bold
mt-1
">


{

task.assigned_user?.full_name
??

"Not Assigned"

}


</p>


</div>







</div>









{

task.alert &&


<div className="
mt-5
rounded-lg
bg-red-50
p-4
">


<h4 className="
font-bold
text-red-700
mb-2
">


AI Clinical Reason


</h4>



<p className="
text-sm
text-gray-700
">


{

task.alert.message

}


</p>


</div>


}









<div className="
flex
flex-wrap
gap-3
mt-5
">





<button


onClick={()=>
handleViewClinical(task)
}


className="
rounded-lg
bg-indigo-600
px-4
py-2
text-sm
font-semibold
text-white
hover:bg-indigo-700
"


>


View Clinical


</button>









<button


onClick={()=>
handleAccept(task.id)
}


disabled={

processingId===task.id

||

task.status==="ACKNOWLEDGED"

}



className="
rounded-lg
bg-blue-600
px-4
py-2
text-sm
font-semibold
text-white
disabled:opacity-50
"


>


{

task.status==="ACKNOWLEDGED"

?

"Accepted ✓"

:

"Accept Task"

}



</button>









<button


onClick={()=>
handleComplete(task.id)
}


disabled={

processingId===task.id

||

task.status==="Completed"

}



className="
rounded-lg
bg-green-600
px-4
py-2
text-sm
font-semibold
text-white
disabled:opacity-50
"


>


{

task.status==="Completed"

?

"Completed ✓"

:

"Complete Task"

}



</button>







</div>







</div>



))


}



</div>



)


}



</div>



);



}



export default NurseTaskTable;