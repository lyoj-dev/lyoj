#include<iostream>
#include<fstream>
#include<assert.h>
#include<unistd.h>
#include<stdlib.h>
#include<sys/resource.h>
#include<sys/wait.h>
#include<jsoncpp/json/json.h>
using namespace std;
string getpath(const char* path) {
	char res[100010] = "";
	#ifdef __linux__
	if(realpath(path, res)) return res;
	#elif _WIN32
	if (_fullpath(res, path, 100010)) return res;
	#endif
    assert(false);
}
string system2(const char* cmd) {
    ofstream fout("/tmp/lang/run.sh");
    fout << "cd /tmp/lang" << endl;
    fout << cmd << endl;
    fout.close();
    system("bash /tmp/lang/run.sh > /tmp/lang/output 2>&1");
    ifstream fin("/tmp/lang/output");
    fin.seekg(0, ios::end);
    int length = fin.tellg();
    fin.seekg(0, ios::beg);
    char buffer[length] = "";
    fin.read(buffer, length);
    fin.close(); string res = buffer;
    while (res.back() == '\n' || res.back() == '\r') res.pop_back();
    return res;
}
bool checkCommandExist(const char* cmd) {
    string ret = system2(("command -v " + string(cmd)).c_str());
    return ret != "";
}
const int cpp_num = 7;
string cpp_ver[] = {"C++98", "C++03", "C++11", "C++14", "C++17", "C++20", "C++23"};
string cpp_cp[] = {"c++98", "c++03", "c++11", "c++14", "c++17", "c++2a", "c++2b"};
void cpp(int argc, char** argv) {
    ofstream fout("/tmp/lang/main.cpp");
    fout << "int main(){return 0;}" << endl; fout.close();
    string version = system2(("VER=$( " + string(argv[2]) + " --version | head -1 )\necho ${VER##* }").c_str());
    string arch = system2("arch");
    cout << "G++ Version: " << version << endl;
    cout << "OS Arch: " << arch << endl;
    Json::Value arr; Json::FastWriter writer;
    for (int i = 0; i < cpp_num; i++) {
        cout << "Checking for " << cpp_ver[i] << "... ";
        string res = system2(("g++ main.cpp -o main --std=" + cpp_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native").c_str());
        if (res != "") {cout << "Failed" << endl; continue;}
        cout << "OK" << endl;
        Json::Value tmp; tmp["name"] = cpp_ver[i] + " (g++" + version + " " + arch + ")";
        tmp["type"] = 0; tmp["mode"] = tmp["highlight-mode"] = "cpp";
        tmp["source_path"] = "main.cpp"; 
        tmp["command"] = "g++ main.cpp -o main --std=" + cpp_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native";
        tmp["exec_command"] = "./main";
        arr.append(tmp);
        tmp["name"] = cpp_ver[i] + " O2 (g++" + version + " " + arch + ")";
        tmp["command"] = "g++ main.cpp -o main --std=" + cpp_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native -O2";
        arr.append(tmp);
    } fout.open(argv[3]);
    fout << writer.write(arr);
    fout.close(); cout << "Success!" << endl;
}
const int c_num = 7;
string c_ver[] = {"C89", "C90", "C99", "C11", "C17", "C18", "C20"};
string c_cp[] = {"c89", "c90", "c99", "c11", "c17", "c18", "c2x"};
void c(int argc, char** argv) {
    ofstream fout("/tmp/lang/main.c");
    fout << "#include<stdio.h>\nint main(){return 0;}" << endl; fout.close();
    string version = system2(("VER=$( " + string(argv[2]) + " --version | head -1 )\necho ${VER##* }").c_str());
    string arch = system2("arch");
    cout << "GCC Version: " << version << endl;
    cout << "OS Arch: " << arch << endl;
    Json::Value arr; Json::FastWriter writer;
    for (int i = 0; i < c_num; i++) {
        cout << "Checking for " << c_ver[i] << "... ";
        string res = system2(("gcc main.c -o main --std=" + c_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native").c_str());
        if (res != "") {cout << "Failed" << endl; continue;}
        cout << "OK" << endl;
        Json::Value tmp; tmp["name"] = c_ver[i] + " (gcc" + version + " " + arch + ")";
        tmp["type"] = 0; tmp["mode"] = tmp["highlight-mode"] = "c";
        tmp["source_path"] = "main.c"; 
        tmp["command"] = "gcc main.c -o main --std=" + c_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native";
        tmp["exec_command"] = "./main";
        arr.append(tmp);
        tmp["name"] = c_ver[i] + " O2 (gcc" + version + " " + arch + ")";
        tmp["command"] = "gcc main.c -o main --std=" + c_cp[i] + " -DONLINE_JUDGE -Wall -fno-asm -lm -march=native -O2";
        arr.append(tmp);
    } fout.open(argv[3]);
    fout << writer.write(arr);
    fout.close(); cout << "Success!" << endl;
}
int main(int argc, char** argv) {
    if (argc < 4) {
        cout << "Usage: " << argv[0] << " [type] [path] [to]" << endl;
        return 0;
    } if (!checkCommandExist(argv[2])) {
        cout << "Command '" << argv[2] << "' is invalid" << endl;
        return 0;
    } if (access("/tmp/lang", 0) == -1) system2("mkdir /tmp/lang");
    if (string(argv[1]) == "g++") cpp(argc, argv);
    else if (string(argv[1]) == "gcc") c(argc, argv);
    else return cout << "Unsupported Language Type!" << endl, 0;
}