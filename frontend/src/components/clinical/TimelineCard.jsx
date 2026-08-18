export default function TimelineCard({
    event
})
{


const getColor = ()=>{


    if(event.severity==="CRITICAL")
        return "border-red-500";


    if(event.category==="AI Intelligence")
        return "border-purple-500";


    if(event.category==="Clinical Observation")
        return "border-blue-500";


    if(event.category==="Clinical Action")
        return "border-green-500";


    return "border-gray-300";


};





return (

<div
className={`
border-l-4
${getColor()}
bg-white
shadow
rounded
p-4
`}
>


<div className="flex justify-between">


<h3 className="font-bold text-lg">

{event.title}

</h3>


<span className="text-sm text-gray-500">

{
new Date(
event.date
)
.toLocaleString()
}

</span>


</div>





<p className="mt-2">

{event.clinical_summary}

</p>





<div className="mt-3 text-sm">


<p>
<b>Category:</b>
{" "}
{event.category}
</p>



<p>
<b>Source:</b>
{" "}
{event.source}
</p>



</div>





{
event.severity &&

<div
className="
mt-3
inline-block
bg-red-100
text-red-700
px-3
py-1
rounded-full
"
>

{event.severity}

</div>

}





{
Object.keys(event.data).length > 0 &&


<div className="mt-4">


<h4 className="font-semibold">
Clinical Data
</h4>



<pre
className="
bg-gray-100
p-3
rounded
text-xs
overflow-auto
"
>

{
JSON.stringify(
event.data,
null,
2
)
}

</pre>


</div>

}



</div>

);


}